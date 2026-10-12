<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Post;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\Subscription;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One small CRUD engine for the CMS and catalogue: each resource declares its
 * model, list columns and form fields. Keeps the admin consistent and the code small.
 */
class ResourceController extends Controller
{
    protected function resources(): array
    {
        return [
            'pages' => [
                'model' => Page::class, 'label' => 'Page', 'plural' => 'Pages', 'icon' => 'file-text',
                'intro' => 'Static pages like About, Terms and Privacy. Body supports Markdown.',
                'columns' => ['title' => 'Title', 'slug' => 'URL', 'placement' => 'Shown in', 'is_published' => 'Live'],
                'order' => ['position', 'asc'],
                'view' => fn ($m) => route('pages.show', $m->slug),
                'fields' => [
                    'title' => ['Title', 'text', 'required|string|max:120'],
                    'slug' => ['URL slug', 'slug', 'nullable|string|max:120|alpha_dash'],
                    'body' => ['Body (Markdown)', 'markdown', 'required|string'],
                    'excerpt' => ['Excerpt', 'text', 'nullable|string|max:255'],
                    'meta_title' => ['SEO title', 'text', 'nullable|string|max:120'],
                    'meta_description' => ['SEO description', 'text', 'nullable|string|max:255'],
                    'placement' => ['Show link in', 'select', 'required|in:header,footer,none', ['header' => 'Header nav', 'footer' => 'Footer', 'none' => 'Nowhere (link manually)']],
                    'position' => ['Order', 'number', 'nullable|integer'],
                    'is_published' => ['Published', 'bool', 'nullable|boolean'],
                ],
            ],
            'posts' => [
                'model' => Post::class, 'label' => 'Post', 'plural' => 'Blog posts', 'icon' => 'newspaper',
                'intro' => 'Marketing and career content. Use the AI writer for a first draft, then make it yours.',
                'columns' => ['emoji' => '', 'title' => 'Title', 'category' => 'Category', 'is_published' => 'Live', 'published_at' => 'Date'],
                'order' => ['published_at', 'desc'],
                'view' => fn ($m) => route('blog.show', $m->slug),
                'ai' => true,
                'fields' => [
                    'title' => ['Title', 'text', 'required|string|max:160'],
                    'slug' => ['URL slug', 'slug', 'nullable|string|max:160|alpha_dash'],
                    'emoji' => ['Emoji', 'text', 'nullable|string|max:16'],
                    'category' => ['Category', 'text', 'nullable|string|max:40'],
                    'excerpt' => ['Excerpt', 'text', 'nullable|string|max:255'],
                    'body' => ['Body (Markdown)', 'markdown', 'required|string'],
                    'cover_path' => ['Cover image', 'image', 'nullable|image|max:4096'],
                    'meta_description' => ['SEO description', 'text', 'nullable|string|max:255'],
                    'read_minutes' => ['Read time (min)', 'number', 'nullable|integer|between:1,60'],
                    'published_at' => ['Publish at', 'datetime', 'nullable|date'],
                    'is_published' => ['Published', 'bool', 'nullable|boolean'],
                ],
            ],
            'faqs' => [
                'model' => Faq::class, 'label' => 'FAQ', 'plural' => 'FAQs', 'icon' => 'circle-help',
                'intro' => 'FAQs power the FAQ page, pricing page and the Hail assistant’s knowledge base.',
                'columns' => ['question' => 'Question', 'audience' => 'Audience', 'is_published' => 'Live'],
                'order' => ['position', 'asc'],
                'fields' => [
                    'question' => ['Question', 'text', 'required|string|max:255'],
                    'answer' => ['Answer', 'textarea', 'required|string|max:3000'],
                    'audience' => ['Audience', 'select', 'required|in:everyone,talent,hirers', ['everyone' => 'Everyone', 'talent' => 'Talent', 'hirers' => 'Employers & clients']],
                    'position' => ['Order', 'number', 'nullable|integer'],
                    'is_published' => ['Published', 'bool', 'nullable|boolean'],
                ],
            ],
            'testimonials' => [
                'model' => Testimonial::class, 'label' => 'Testimonial', 'plural' => 'Testimonials', 'icon' => 'quote',
                'intro' => 'Use real quotes with permission. The starter ones are clearly labelled samples — replace them.',
                'columns' => ['name' => 'Name', 'role' => 'Role', 'is_published' => 'Live'],
                'order' => ['position', 'asc'],
                'fields' => [
                    'name' => ['Name', 'text', 'required|string|max:100'],
                    'role' => ['Role / company', 'text', 'nullable|string|max:120'],
                    'quote' => ['Quote', 'textarea', 'required|string|max:600'],
                    'position' => ['Order', 'number', 'nullable|integer'],
                    'is_published' => ['Published', 'bool', 'nullable|boolean'],
                ],
            ],
            'categories' => [
                'model' => SkillCategory::class, 'label' => 'Category', 'plural' => 'Skill categories', 'icon' => 'shapes',
                'intro' => 'Top level of the skill taxonomy.',
                'columns' => ['name' => 'Name', 'slug' => 'Slug', 'icon' => 'Icon'],
                'order' => ['position', 'asc'],
                'fields' => [
                    'name' => ['Name', 'text', 'required|string|max:80'],
                    'slug' => ['Slug', 'slug', 'nullable|string|max:80|alpha_dash'],
                    'icon' => ['Icon (lucide name)', 'text', 'nullable|string|max:40'],
                    'description' => ['Description', 'textarea', 'nullable|string|max:500'],
                    'position' => ['Order', 'number', 'nullable|integer'],
                ],
            ],
            'skills' => [
                'model' => Skill::class, 'label' => 'Skill', 'plural' => 'Skills', 'icon' => 'tags',
                'intro' => 'Skills professionals can claim. Synonyms improve search (e.g. “k8s” for Kubernetes).',
                'columns' => ['name' => 'Name', 'category.name' => 'Category', 'synonyms' => 'Synonyms', 'is_active' => 'Active'],
                'order' => ['name', 'asc'],
                'with' => ['category'],
                'fields' => [
                    'name' => ['Name', 'text', 'required|string|max:80'],
                    'slug' => ['Slug', 'slug', 'nullable|string|max:80|alpha_dash'],
                    'skill_category_id' => ['Category', 'select', 'required|exists:skill_categories,id', fn () => SkillCategory::orderBy('position')->pluck('name', 'id')->all()],
                    'synonyms' => ['Synonyms (comma separated)', 'text', 'nullable|string|max:255'],
                    'description' => ['Description', 'textarea', 'nullable|string|max:500'],
                    'is_active' => ['Active', 'bool', 'nullable|boolean'],
                ],
            ],
            'plans' => [
                'model' => Plan::class, 'label' => 'Plan', 'plural' => 'Plans', 'icon' => 'gem',
                'intro' => 'Recruiter subscriptions. Prices are in kobo (₦25,000 = 2500000).',
                'columns' => ['name' => 'Name', 'price_minor' => 'Price (kobo)', 'contact_unlocks' => 'Unlocks', 'recruiter_seats' => 'Seats', 'is_active' => 'Active'],
                'order' => ['position', 'asc'],
                'fields' => [
                    'name' => ['Name', 'text', 'required|string|max:60'],
                    'slug' => ['Slug', 'slug', 'nullable|string|max:60|alpha_dash'],
                    'tagline' => ['Tagline', 'text', 'nullable|string|max:120'],
                    'price_minor' => ['Price (kobo)', 'number', 'required|integer|min:0'],
                    'currency' => ['Currency', 'select', 'required|in:NGN,USD', ['NGN' => 'NGN', 'USD' => 'USD']],
                    'interval' => ['Billing interval', 'select', 'required|in:month,year,custom', ['month' => 'Monthly', 'year' => 'Yearly', 'custom' => 'Custom quote']],
                    'contact_unlocks' => ['Contact unlocks per period', 'number', 'required|integer|min:0'],
                    'recruiter_seats' => ['Recruiter seats', 'number', 'required|integer|min:1'],
                    'active_jobs' => ['Active job posts', 'number', 'required|integer|min:0'],
                    'features' => ['Features (one per line)', 'lines', 'nullable|string|max:2000'],
                    'is_featured' => ['Highlight as most popular', 'bool', 'nullable|boolean'],
                    'is_custom' => ['Custom / contact sales', 'bool', 'nullable|boolean'],
                    'is_active' => ['Active', 'bool', 'nullable|boolean'],
                    'position' => ['Order', 'number', 'nullable|integer'],
                ],
            ],
        ];
    }

    protected function def(string $resource): array
    {
        $defs = $this->resources();
        abort_unless(isset($defs[$resource]), 404);

        return $defs[$resource] + ['key' => $resource];
    }

    public function index(Request $request, string $resource)
    {
        $def = $this->def($resource);
        $titleField = array_key_first($def['fields']);
        $items = $def['model']::query()->with($def['with'] ?? [])
            ->when($request->q, fn ($q, $t) => $q->where($titleField, 'like', "%$t%"))
            ->orderBy(...$def['order'])->paginate(30)->withQueryString();

        return view('admin.resources.index', compact('def', 'items'));
    }

    public function create(string $resource)
    {
        $def = $this->def($resource);

        return view('admin.resources.form', ['def' => $def, 'item' => new $def['model'](['is_published' => true, 'is_active' => true]), 'options' => $this->options($def)]);
    }

    public function store(Request $request, string $resource)
    {
        $def = $this->def($resource);
        $item = new $def['model'];
        $this->persist($request, $def, $item);

        return redirect()->route('admin.resources.index', $resource)->with('success', $def['label'].' created.');
    }

    public function edit(string $resource, int $id)
    {
        $def = $this->def($resource);

        return view('admin.resources.form', ['def' => $def, 'item' => $def['model']::findOrFail($id), 'options' => $this->options($def)]);
    }

    public function update(Request $request, string $resource, int $id)
    {
        $def = $this->def($resource);
        $this->persist($request, $def, $def['model']::findOrFail($id));

        return back()->with('success', $def['label'].' saved.');
    }

    public function destroy(string $resource, int $id)
    {
        $def = $this->def($resource);
        $item = $def['model']::findOrFail($id);
        if ($resource === 'skills' && $item->assessments()->exists()) {
            return back()->with('error', 'This skill has assessments. Deactivate it instead of deleting.');
        }
        if ($resource === 'categories' && $item->skills()->exists()) {
            return back()->with('error', 'Move or delete this category’s skills first.');
        }
        if ($resource === 'plans' && Subscription::where('plan_id', $item->id)->exists()) {
            return back()->with('error', 'This plan has subscribers. Deactivate it instead.');
        }
        $item->delete();
        AuditLog::record($resource.'.deleted', null, ['id' => $id]);

        return redirect()->route('admin.resources.index', $resource)->with('success', $def['label'].' deleted.');
    }

    protected function persist(Request $request, array $def, $item): void
    {
        $rules = collect($def['fields'])->map(fn ($f) => $f[2])->all();
        $data = $request->validate($rules);
        $titleField = array_key_first($def['fields']);

        foreach ($def['fields'] as $key => $f) {
            match ($f[1]) {
                'bool' => $data[$key] = $request->boolean($key),
                'lines' => $data[$key] = collect(preg_split('/\r?\n/', (string) ($data[$key] ?? '')))->map(fn ($l) => trim($l))->filter()->values()->all(),
                'slug' => $data[$key] = Str::slug($data[$key] ?: $data[$titleField]),
                'image' => (function () use ($request, $key, &$data, $item) {
                    unset($data[$key]);
                    if ($request->hasFile($key)) {
                        if ($item->$key) {
                            Storage::disk('public')->delete($item->$key);
                        }
                        $data[$key] = $request->file($key)->store('cms', 'public');
                    }
                })(),
                default => null,
            };
        }
        if (array_key_exists('position', $data) && $data['position'] === null) {
            $data['position'] = 0;
        }
        if ($def['key'] === 'posts') {
            $data['author_id'] = $item->author_id ?? $request->user()->id;
            if ($data['is_published'] && empty($data['published_at'])) {
                $data['published_at'] = now();
            }
            $data['excerpt'] = $data['excerpt'] ?: Str::limit(trim(strip_tags(Str::markdown($data['body']))), 150);
            $data['read_minutes'] = $data['read_minutes'] ?: max(1, (int) ceil(str_word_count(strip_tags($data['body'])) / 220));
        }

        // Unique slug.
        if (isset($data['slug'])) {
            $base = $data['slug'];
            $i = 1;
            while ($def['model']::where('slug', $data['slug'])->when($item->exists, fn ($q) => $q->where('id', '!=', $item->id))->exists()) {
                $data['slug'] = $base.'-'.(++$i);
            }
        }

        $item->fill($data)->save();
        AuditLog::record($def['key'].'.saved', $item);
    }

    protected function options(array $def): array
    {
        return collect($def['fields'])->filter(fn ($f) => $f[1] === 'select')->map(fn ($f) => is_callable($f[3]) ? ($f[3])() : $f[3])->all();
    }
}
