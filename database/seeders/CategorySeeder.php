<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Web Development',
                'slug' => 'web-development',
                'description' => 'Full-stack development, frontend frameworks, backend APIs, and database engineering.',
                'icon' => 'code',
                'is_active' => true,
            ],
            [
                'name' => 'UI/UX Design',
                'slug' => 'ui-ux-design',
                'description' => 'User interface design, wireframing, design systems, and product ergonomics.',
                'icon' => 'design_services',
                'is_active' => true,
            ],
            [
                'name' => 'Data Science & AI',
                'slug' => 'data-science-ai',
                'description' => 'Machine learning, data analytics, predictive modeling, and pipeline architecture.',
                'icon' => 'query_stats',
                'is_active' => true,
            ],
            [
                'name' => 'Systems & DevOps',
                'slug' => 'systems-devops',
                'description' => 'Cloud infrastructure, CI/CD automation, server architecture, and security audits.',
                'icon' => 'terminal',
                'is_active' => true,
            ],
            [
                'name' => 'Writing & Content',
                'slug' => 'writing-content',
                'description' => 'Technical copywriting, brand storytelling, documentation, and research synthesis.',
                'icon' => 'edit_note',
                'is_active' => true,
            ],
            [
                'name' => 'Urban Ecology',
                'slug' => 'urban-ecology',
                'description' => 'Sustainable engineering, hydroponics, urban architecture, and ecological planning.',
                'icon' => 'eco',
                'is_active' => true,
            ],
            [
                'name' => 'Community & Mentorship',
                'slug' => 'community-mentorship',
                'description' => 'Skill tutoring, career strategy, code reviews, and peer advisory sessions.',
                'icon' => 'groups',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
