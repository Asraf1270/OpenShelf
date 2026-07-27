<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate XML sitemap for OpenShelf public pages and books';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating sitemap...');

        $sitemap = Sitemap::create();

        // 1. Static Public Pages
        $staticRoutes = [
            ['url' => '/', 'priority' => 1.0, 'freq' => Url::CHANGE_FREQUENCY_DAILY],
            ['url' => '/books', 'priority' => 0.9, 'freq' => Url::CHANGE_FREQUENCY_HOURLY],
            ['url' => '/about', 'priority' => 0.6, 'freq' => Url::CHANGE_FREQUENCY_MONTHLY],
            ['url' => '/faq', 'priority' => 0.6, 'freq' => Url::CHANGE_FREQUENCY_MONTHLY],
            ['url' => '/contact', 'priority' => 0.5, 'freq' => Url::CHANGE_FREQUENCY_MONTHLY],
            ['url' => '/terms', 'priority' => 0.3, 'freq' => Url::CHANGE_FREQUENCY_MONTHLY],
            ['url' => '/privacy', 'priority' => 0.3, 'freq' => Url::CHANGE_FREQUENCY_MONTHLY],
            ['url' => '/guidelines', 'priority' => 0.3, 'freq' => Url::CHANGE_FREQUENCY_MONTHLY],
            ['url' => '/announcements', 'priority' => 0.6, 'freq' => Url::CHANGE_FREQUENCY_WEEKLY],
            ['url' => '/support-us', 'priority' => 0.5, 'freq' => Url::CHANGE_FREQUENCY_MONTHLY],
        ];

        foreach ($staticRoutes as $route) {
            $sitemap->add(
                Url::create($route['url'])
                    ->setPriority($route['priority'])
                    ->setChangeFrequency($route['freq'])
            );
        }

        // 2. Dynamic Public Book Pages
        $bookCount = 0;
        Book::query()->chunk(100, function ($books) use ($sitemap, &$bookCount) {
            foreach ($books as $book) {
                $sitemap->add(
                    Url::create("/book?id={$book->id}")
                        ->setLastModificationDate($book->updated_at ?? now())
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                        ->setPriority(0.8)
                );
                $bookCount++;
            }
        });

        $path = public_path('sitemap.xml');
        $sitemap->writeToFile($path);

        $this->info("Sitemap generated successfully at {$path} with {$bookCount} book URLs.");

        // 3. Keep robots.txt updated with sitemap URL
        $this->updateRobotsTxt();

        return self::SUCCESS;
    }

    private function updateRobotsTxt(): void
    {
        $robotsPath = public_path('robots.txt');
        $sitemapUrl = url('sitemap.xml');

        $content = "User-agent: *\n";
        $content .= "Allow: /\n\n";
        $content .= "# Disallow private and administrative endpoints\n";
        $content .= "Disallow: /admin\n";
        $content .= "Disallow: /admin/\n";
        $content .= "Disallow: /api/\n";
        $content .= "Disallow: /settings\n";
        $content .= "Disallow: /settings/\n";
        $content .= "Disallow: /my-borrowed\n";
        $content .= "Disallow: /requests\n";
        $content .= "Disallow: /notifications\n";
        $content .= "Disallow: /borrow-request\n";
        $content .= "Disallow: /return-book\n";
        $content .= "Disallow: /confirm-return\n";
        $content .= "Disallow: /add-book\n";
        $content .= "Disallow: /edit-book\n\n";
        $content .= "Sitemap: {$sitemapUrl}\n";

        file_put_contents($robotsPath, $content);
        $this->info("Updated {$robotsPath} with Sitemap URL.");
    }
}
