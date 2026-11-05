<?php

namespace App\Http\Controllers;

class BlogController extends Controller
{
    public function index()
    {
        $filters = [];
        foreach (['q', 'category', 'page'] as $key) {
            $v = request()->query($key);
            $filters[$key] = is_string($v) ? mb_substr(trim($v), 0, 200) : '';
        }

        return $this->render('blog/index', array_merge($this->blog->listing($filters), ['title' => 'Blog · เรื่องเล่าระหว่างทาง', 'blog_page' => true, 'filters' => $filters, 'categories' => $this->blog->public_categories(), 'all_total' => $this->blog->public_total(), 'meta' => seo_page_meta($this->settings, 'blog')]));
    }

    public function show($slug)
    {
        $post = $this->blog->by_slug($slug);
        if (! $post) {
            $target = $this->blog->redirected_post($slug);
            if ($target) {
                set_header('Cache-Control: no-store');
                redirect_to(base_url().'blog/'.rawurlencode($target['slug']), 301);
            }
            show_404();
        }
        $url = base_url().'blog/'.rawurlencode($post['slug']);

        return $this->render('blog/show', ['title' => $post['title'], 'blog_page' => true, 'post' => $post, 'related' => $this->blog->related($post), 'meta' => ['title' => $post['seo_title'] ?: $post['title'].' · RoamVenture', 'description' => $post['seo_description'] ?: $post['excerpt'], 'canonical' => $url, 'image' => tour_image($post['cover'])], 'structured_data' => ['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $post['title'], 'datePublished' => date(DATE_ATOM, strtotime($post['published_at'])), 'dateModified' => date(DATE_ATOM, strtotime($post['updated_at'])), 'author' => ['@type' => 'Person', 'name' => $post['author']], 'image' => tour_image($post['cover']), 'mainEntityOfPage' => $url]]);
    }
}
