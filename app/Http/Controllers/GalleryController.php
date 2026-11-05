<?php

namespace App\Http\Controllers;

class GalleryController extends Controller
{
    public function index()
    {
        $filters = [];
        foreach (['q', 'category', 'page'] as $k) {
            $v = request()->query($k);
            $filters[$k] = is_string($v) ? mb_substr(trim($v), 0, 180) : '';
        }
        $collage = array_column($this->gallery->home_highlights(), 'path');
        if (count($collage) < 5) {
            foreach (['hero', 'krabi', 'nan', 'chiangmai', 'khaoyai'] as $name) {
                if (count($collage) < 5) {
                    $collage[] = 'public/images/'.$name.'.jpg';
                }
            }
        }

        return $this->render('gallery/index', array_merge($this->gallery->listing($filters), ['title' => 'Gallery • บันทึกการเดินทาง', 'gallery_page' => true, 'filters' => $filters, 'categories' => $this->gallery->categories(), 'stats' => $this->gallery->stats(), 'collage' => array_slice(array_values(array_unique($collage)), 0, 5), 'meta' => seo_page_meta($this->settings, 'gallery')]));
    }

    public function show($id)
    {
        $album = $this->gallery->find($id);
        if (! $album || $album['status'] !== 'published') {
            show_404();
        }
        $photos = $this->gallery->photos($id);
        $cover = $photos[0]['path'] ?? 'public/images/hero.jpg';
        foreach ($photos as $p) {
            if ($p['is_cover']) {
                $cover = $p['path'];
                break;
            }
        }

        return $this->render('gallery/show', ['title' => $album['title'], 'gallery_page' => true, 'album' => $album, 'photos' => $photos, 'cover' => $cover, 'next' => $this->gallery->next_album($album), 'meta' => ['description' => mb_substr($album['description'], 0, 200), 'image' => base_url().$cover]]);
    }
}
