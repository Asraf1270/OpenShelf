<?php

namespace App\Http\Controllers;

class StaticPageController extends Controller
{
    public function faq()
    {
        return view('pages.faq', [
            'seoTitle' => 'FAQ - OpenShelf',
            'seoDesc' => 'Frequently asked questions about OpenShelf book sharing platform.',
        ]);
    }

    public function about()
    {
        return view('pages.about', [
            'seoTitle' => 'About Us - OpenShelf',
            'seoDesc' => 'Learn about OpenShelf and our mission to make knowledge accessible on campus.',
        ]);
    }

    public function terms()
    {
        return view('pages.terms', [
            'seoTitle' => 'Terms of Service - OpenShelf',
            'seoDesc' => 'OpenShelf terms of service and community guidelines.',
        ]);
    }

    public function privacy()
    {
        return view('pages.privacy', [
            'seoTitle' => 'Privacy Policy - OpenShelf',
            'seoDesc' => 'How OpenShelf collects, uses, and protects your personal information.',
        ]);
    }

    public function guidelines()
    {
        return view('pages.guidelines', [
            'seoTitle' => 'Community Guidelines - OpenShelf',
            'seoDesc' => 'Community guidelines for respectful book sharing on OpenShelf.',
        ]);
    }
}
