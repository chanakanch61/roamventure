<?php

namespace App\Repositories;

use App\Support\ResolvesServices;
use DOMDocument;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class BlogRepository
{
    use ResolvesServices;

    public $error = '';

    public function slug_taken($slug, $id = 0)
    {
        return DB::table('blog_slugs')->where('slug', $slug)->where('post_id', '!=', (int) $id)->count() || DB::table('blog_posts')->where('slug', $slug)->where('id', '!=', (int) $id)->count();
    }

    public function redirected_post($slug)
    {
        $query = DB::table('blog_slugs as s');
        $this->visible($query);

        return $query->select('p.slug')->join('blog_posts as p', 'p.id', '=', 's.post_id')->where('s.slug', $slug)->first();
    }

    // Reserve current and historical URLs in one unique namespace, in the same transaction as the post.
    public function save_post($id, $data, $expected_updated)
    {
        DB::beginTransaction();
        $ok = true;
        if ($id) {
            $current = DB::select('SELECT * FROM blog_posts WHERE id=? FOR UPDATE', [(int) $id])[0] ?? null;
            if (! $current || $current['status'] === 'trash' || $current['updated_at'] !== $expected_updated) {
                $ok = false;
                $this->error = 'บทความถูกเปลี่ยนแปลง กรุณาโหลดหน้าใหม่ก่อนบันทึก';
            }
        }
        if ($ok && $this->slug_taken($data['slug'], $id)) {
            $ok = false;
            $this->error = 'URL นี้ถูกใช้โดยบทความอื่นหรือลิงก์เก่า กรุณาเปลี่ยน URL';
        }
        if ($ok) {
            try {
                if ($id) {
                    DB::table('blog_posts')->where('id', $id)->update($data);
                } else {
                    $data['created_at'] = date('Y-m-d H:i:s');
                    $id = DB::table('blog_posts')->insertGetId($data);
                }
            } catch (QueryException $e) {
                $ok = false;
            }
        }
        if ($ok) {
            foreach (array_unique([$data['slug'], $current['slug'] ?? $data['slug']]) as $slug) {
                $owner = DB::select('SELECT post_id FROM blog_slugs WHERE slug=? FOR UPDATE', [$slug])[0] ?? null;
                if ($owner) {
                    if ((int) $owner['post_id'] !== (int) $id) {
                        $ok = false;
                        break;
                    }
                } else {
                    try {
                        DB::table('blog_slugs')->insert(['slug' => $slug, 'post_id' => $id]);
                    } catch (QueryException $e) {
                        $ok = false;
                        break;
                    }
                }
            }
        }
        if ($ok) {
            DB::commit();
        } else {
            DB::rollBack();
            if (! $this->error) {
                $this->error = 'บันทึกไม่สำเร็จ URL อาจถูกใช้งานแล้ว กรุณาลองใหม่';
            }
        }

        return $ok ? $id : false;
    }

    public function categories()
    {
        return DB::table('blog_categories')->orderBy('name')->get()->all();
    }

    // Public chips only list categories that currently have readable stories, with their counts.
    public function public_categories()
    {
        $query = DB::table('blog_categories as c');
        $this->visible($query);

        return $query->select('c.id', 'c.name')->selectRaw('COUNT(p.id) total')->join('blog_posts as p', 'p.category_id', '=', 'c.id')->groupBy('c.id', 'c.name')->orderBy('c.name')->get()->all();
    }

    public function public_total()
    {
        $query = DB::table('blog_posts as p');
        $this->visible($query);

        return $query->count();
    }

    public function latest_public()
    {
        $query = DB::table('blog_posts as p');
        $this->visible($query);

        return $query->select('p.id', 'p.title', 'p.slug', 'p.excerpt', 'p.cover', 'p.cover_alt', 'p.published_at', 'c.name as category_name')->leftJoin('blog_categories as c', 'c.id', '=', 'p.category_id')->orderBy('p.published_at', 'DESC')->orderBy('p.id', 'DESC')->limit(3)->get()->all();
    }

    public function related($post)
    {
        $query = DB::table('blog_posts as p');
        $this->visible($query);
        $query->select('p.*', 'c.name as category_name')->leftJoin('blog_categories as c', 'c.id', '=', 'p.category_id')->where('p.id', '!=', (int) $post['id']);
        if (! empty($post['category_id'])) {
            $query->orderByRaw('(p.category_id = '.(int) $post['category_id'].') DESC');
        }

        return $query->orderBy('p.published_at', 'DESC')->orderBy('p.id', 'DESC')->limit(3)->get()->all();
    }

    public function find($id)
    {
        return DB::table('blog_posts')->where('id', (int) $id)->first();
    }

    public function visible($query)
    {
        $query->where('p.status', 'published')->where('p.published_at', '<=', date('Y-m-d H:i:s'));
    }

    private function filters($filters, $public)
    {
        $query = DB::table('blog_posts as p')->leftJoin('blog_categories as c', 'c.id', '=', 'p.category_id');
        if ($public) {
            $this->visible($query);
        } elseif (! empty($filters['status'])) {
            $query->where('p.status', $filters['status']);
        } else {
            $query->where('p.status', '!=', 'trash');
        }
        if (! empty($filters['category'])) {
            $query->where('p.category_id', (int) $filters['category']);
        }
        if (! empty($filters['q'])) {
            $like = '%'.addcslashes($filters['q'], '%_\\').'%';
            $query->where(function ($q) use ($like) {
                $q->where('p.title', 'like', $like)->orWhere('p.excerpt', 'like', $like)->orWhere('p.tags', 'like', $like);
            });
        }

        return $query;
    }

    public function listing($filters, $public = true)
    {
        $query = $this->filters($filters, $public);
        $total = $query->count();
        $page = max(1, min((int) ($filters['page'] ?? 1), max(1, (int) ceil($total / 12))));
        $query = $this->filters($filters, $public);
        $rows = $query->select('p.*', 'c.name as category_name')->orderBy($public ? 'p.published_at' : 'p.updated_at', 'DESC')->orderBy('p.id', 'DESC')->limit(12)->offset(($page - 1) * 12)->get()->all();

        return ['posts' => $rows, 'total' => $total, 'page' => $page, 'pages' => (int) ceil($total / 12)];
    }

    public function by_slug($slug)
    {
        $query = DB::table('blog_posts as p');
        $this->visible($query);

        return $query->select('p.*', 'c.name as category_name')->leftJoin('blog_categories as c', 'c.id', '=', 'p.category_id')->where('p.slug', $slug)->first();
    }

    // Rebuild HTML from an explicit allowlist. No input attributes are copied wholesale.
    public function clean_html($html, $imageDirectory = 'blog')
    {
        if (! in_array($imageDirectory, ['blog', 'destinations'], true)) {
            $imageDirectory = 'blog';
        }
        $doc = new DOMDocument('1.0', 'UTF-8');
        $before = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($before);
        $walk = function ($node) use (&$walk, $imageDirectory) {
            if ($node->nodeType === XML_TEXT_NODE) {
                return htmlspecialchars($node->nodeValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                return '';
            }
            $tag = strtolower($node->nodeName);
            if ($tag === 'img') {
                $src = $node->getAttribute('src');
                $prefix = base_url();
                $path = strpos($src, $prefix) === 0 ? substr($src, strlen($prefix)) : $src;
                if (! preg_match('~^public/uploads/'.$imageDirectory.'/[a-f0-9]+\.(jpg|jpeg|png|webp)$~D', $path)) {
                    return '';
                }

                return '<img src="'.htmlspecialchars(base_url().$path, ENT_QUOTES, 'UTF-8').'" alt="'.htmlspecialchars(mb_substr($node->getAttribute('alt'), 0, 200), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'" loading="lazy">';
            }
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'svg', 'math', 'form', 'input', 'button', 'textarea'], true)) {
                return '';
            }
            $inner = '';
            foreach ($node->childNodes as $child) {
                $inner .= $walk($child);
            }
            $layoutTags = $imageDirectory === 'destinations' ? ['section', 'article', 'figure', 'figcaption', 'details', 'summary', 'span'] : [];
            if (! in_array($tag, array_merge(['p', 'div', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li', 'blockquote', 'br', 'hr', 'a'], $layoutTags), true)) {
                return $inner;
            }
            $attrs = '';
            if ($tag === 'a') {
                $href = trim($node->getAttribute('href'));
                if (preg_match('~^https?://~i', $href) && filter_var($href, FILTER_VALIDATE_URL)) {
                    $attrs = ' href="'.htmlspecialchars($href, ENT_QUOTES, 'UTF-8').'" rel="noopener noreferrer"';
                }
            }
            if ($imageDirectory === 'destinations') {
                if ($tag === 'a' && $node->getAttribute('href') === '#destination-programs') {
                    $attrs = ' href="#destination-programs"';
                }
                $layouts = ['section' => ['intro', 'highlights', 'split', 'split-reverse', 'mosaic', 'callout', 'faq', 'cta'], 'div' => ['cards', 'gallery'], 'p' => ['kicker'], 'span' => ['kicker'], 'a' => ['button']];
                $layout = $node->getAttribute('data-onpage');
                if (isset($layouts[$tag]) && in_array($layout, $layouts[$tag], true)) {
                    $attrs .= ' data-onpage="'.$layout.'"';
                }
            }

            return '<'.$tag.$attrs.'>'.$inner.(in_array($tag,['br', 'hr'],true) ? '' : '</'.$tag.'>');
        };
        $out = '';
        $body = $doc->getElementsByTagName('body')->item(0);
        if ($body) {
            foreach ($body->childNodes as $child) {
                $out .= $walk($child);
            }
        }

return $out;
    }
}
