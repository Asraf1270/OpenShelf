<?php

namespace Tests\Unit;

use App\Services\BookCoverService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookCoverServiceTest extends TestCase
{
    public function test_process_stores_only_the_main_cover_file(): void
    {
        config()->set('filesystems.default', 'local');
        Storage::fake('local');

        $tempImagePath = tempnam(sys_get_temp_dir(), 'cover_test_');
        $image = imagecreatetruecolor(100, 150);
        $background = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $background);
        imagejpeg($image, $tempImagePath, 90);
        imagedestroy($image);

        $file = new UploadedFile($tempImagePath, 'cover.jpg', 'image/jpeg', null, true);

        $service = new BookCoverService();
        $result = $service->process($file, 'book-123');

        $this->assertArrayHasKey('filename', $result);
        $this->assertTrue(Storage::disk('local')->exists('book_cover/' . $result['filename']));
        $this->assertFalse(Storage::disk('local')->exists('book_cover/thumb_' . $result['filename']));
        $this->assertCount(1, Storage::disk('local')->allFiles('book_cover'));

        @unlink($tempImagePath);
    }
}
