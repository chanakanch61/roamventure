<?php

namespace App\Repositories;

use App\Support\ResolvesServices;
use Illuminate\Support\Facades\DB;

class GalleryRepository
{
    use ResolvesServices;

    public function home_highlights()
    {
        return DB::table('gallery_photos as p')->select('p.path', 'p.caption', 'a.id as album_id', 'a.title as album_title', 'a.location', 'a.category')
            ->selectRaw('(SELECT COUNT(*) FROM gallery_photos counts WHERE counts.album_id=a.id) AS photo_count')
            ->join('gallery_albums as a', 'a.id', '=', 'p.album_id')
            ->where('a.status', 'published')->orderBy('p.is_cover', 'DESC')->orderBy('a.sort_order')
            ->orderBy('a.id', 'DESC')->orderBy('p.sort_order')->orderBy('p.id')->limit(8)->get()->all();
    }

    public function find($id)
    {
        return DB::table('gallery_albums')->where('id', (int) $id)->first();
    }

    public function photos($id)
    {
        return DB::table('gallery_photos')->where('album_id', (int) $id)->orderBy('sort_order')->orderBy('id')->get()->all();
    }

    public function listing($filters, $admin = false)
    {
        $query = DB::table('gallery_albums');
        if (! $admin) {
            $query->where('status', 'published');
        } elseif (in_array($filters['status'] ?? '', ['draft', 'published'], true)) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (! empty($filters['q'])) {
            $like = '%'.addcslashes($filters['q'], '%_\\').'%';
            $query->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)->orWhere('location', 'like', $like)->orWhere('description', 'like', $like);
            });
        }
        $total = $query->count();
        $pages = max(1, (int) ceil($total / 12));
        $page = min($pages, max(1, (int) ($filters['page'] ?? 1)));
        $albums = $query->orderBy('sort_order')->orderBy('id', 'DESC')->limit(12)->offset(($page - 1) * 12)->get()->all();
        foreach ($albums as &$a) {
            $a['photo_count'] = DB::table('gallery_photos')->where('album_id', $a['id'])->count();
            $a['previews'] = $this->previews($a['id']);
            $a['cover'] = $a['previews'][0] ?? '';
        } unset($a);

        return compact('albums', 'total', 'page', 'pages');
    }

    public function previews($id, $limit = 4)
    {
        return array_column(DB::table('gallery_photos')->select('path')->where('album_id', (int) $id)->orderBy('is_cover', 'DESC')->orderBy('sort_order')->orderBy('id')->limit($limit)->get()->all(), 'path');
    }

    public function stats()
    {
        $published = DB::table('gallery_albums')->select('id')->where('status', 'published');

        return ['albums' => DB::table('gallery_albums')->where('status', 'published')->count(),
            'photos' => DB::table('gallery_photos')->whereIn('album_id', $published)->count(),
            'places' => array_column(DB::table('gallery_albums')->select('location')->distinct()->where('status', 'published')->where('location', '!=', '')->orderBy('location')->limit(12)->get()->all(), 'location')];
    }

    public function next_album($album)
    {
        $next = DB::table('gallery_albums')->where('status', 'published')->where(function ($q) use ($album) {
            $q->where('sort_order', '>', $album['sort_order'])->orWhere(function ($q) use ($album) {
                $q->where('sort_order', $album['sort_order'])->where('id', '<', $album['id']);
            });
        })
            ->orderBy('sort_order')->orderBy('id', 'DESC')->limit(1)->first();
        // The last chapter loops back to the first, so the page never ends on a dead end.
        if (! $next) {
            $next = DB::table('gallery_albums')->where('status', 'published')->where('id', '!=', $album['id'])->orderBy('sort_order')->orderBy('id', 'DESC')->limit(1)->first();
        }
        if ($next) {
            $next['cover'] = $this->previews($next['id'], 1)[0] ?? '';
        }

        return $next;
    }

    public function categories($admin = false)
    {
        $query = DB::table('gallery_albums');
        if (! $admin) {
            $query->where('status', 'published');
        }

        return array_column($query->select('category')->distinct()->where('category', '!=', '')->orderBy('category')->get()->all(), 'category');
    }

    public function remove_files($paths)
    {
        foreach ($paths as $path) {
            if (preg_match('~^public/uploads/gallery/[a-f0-9]+\.(jpg|jpeg|png|webp)$~D',$path) && is_file(base_path($path))) {
                unlink(base_path($path));
            }
        }
    }
}
