<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Faker\Factory;
use Faker\Generator;

class DemoDataSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'DemoPass1!';
    private const USER_COUNT = 20;
    private const EVENT_PARTICIPATIONS  = 5;

    public function run(): void
    {
        $faker = Factory::create();

        $now = date('Y-m-d H:i:s');
        $hash = password_hash(self::DEMO_PASSWORD, PASSWORD_BCRYPT, ['cost' => 12]);

        $userIds = [];

        for ($i = 1; $i <= self::USER_COUNT; $i++) {
            $email = sprintf('demo.alum%02d@iit.ac.lk', $i);

            $this->db->table('users')->insert([
                'email' => $email,
                'password' => $hash,
                'role' => 'alumni',
                'is_verified' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $userIds[] = (int) $this->db->insertID();
        }

        $degreeTitles = [
            'BSc (Hons) Computer Science',
            'BEng Software Engineering',
            'BSc Information Technology',
            'BSc Data Science',
            'BEng Computer Systems Engineering',
        ];

        foreach ($userIds as $idx => $userId) {
            $first = $faker->firstName();
            $last = $faker->lastName();
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $first . '-' . $last . '-' . ($idx + 1)));

            $this->db->table('alumni_profiles')->insert([
                'user_id' => $userId,
                'first_name' => $first,
                'last_name' => $last,
                'bio' => $faker->optional(0.85)->paragraph(3),
                'linkedin_url' => 'https://www.linkedin.com/in/' . $slug,
                'profile_image' => null,
                'is_featured' => $idx < 3,
                'appearance_count' => $faker->numberBetween(0, 120),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->db->table('certifications')->insert([
                'user_id' => $userId,
                'name' => $faker->randomElement([
                    'AWS Certified Solutions Architect',
                    'Certified Kubernetes Administrator',
                    'Google Professional Cloud Architect',
                    'Microsoft Azure Administrator',
                    'Cisco CCNA',
                    'CompTIA Security+',
                ]),
                'issuing_organization' => $faker->company(),
                'url' => $faker->optional(0.7)->url(),
                'completion_date' => $faker->dateTimeBetween('-4 years', '-3 months')->format('Y-m-d'),
            ]);

            $this->db->table('courses')->insert([
                'user_id' => $userId,
                'name' => $faker->randomElement([
                    'Advanced Web Application Security',
                    'Distributed Systems',
                    'Machine Learning Fundamentals',
                    'Cloud Native Development',
                    'REST API Design',
                ]),
                'provider' => $faker->randomElement(['Coursera', 'Udemy', 'edX', 'Pluralsight', 'LinkedIn Learning']),
                'url' => $faker->optional(0.75)->url(),
                'completion_date' => $faker->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            ]);

            $this->db->table('degrees')->insert([
                'user_id' => $userId,
                'title' => $degreeTitles[$idx % count($degreeTitles)],
                'university' => 'Informatics Institute of Technology',
                'url' => $faker->optional(0.5)->url(),
                'completion_date' => $faker->dateTimeBetween('-12 years', '-1 year')->format('Y-m-d'),
            ]);

            $isCurrent = $faker->boolean(35);
            $start = $faker->dateTimeBetween('-10 years', $isCurrent ? '-6 months' : '-3 years');
            $end = $isCurrent ? null : $faker->dateTimeBetween($start, '-1 month');

            $this->db->table('employment_history')->insert([
                'user_id' => $userId,
                'company' => $faker->company(),
                'role' => $faker->jobTitle(),
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end ? $end->format('Y-m-d') : null,
                'is_current' => $isCurrent,
            ]);

            $this->db->table('licences')->insert([
                'user_id' => $userId,
                'name'=> $faker->randomElement([
                    'Professional Engineer (PE)',
                    'Chartered IT Professional',
                    'Project Management Professional (PMP)',
                    'Certified Scrum Master',
                    'ITIL Foundation',
                ]),
                'url' => $faker->optional(0.6)->url(),
                'completion_date' => $faker->dateTimeBetween('-6 years', '-6 months')->format('Y-m-d'),
            ]);
        }

        $this->seedEventParticipations($faker, $userIds, $now);
    }

    /**
     * @param list<int> $userIds
     */
    private function seedEventParticipations(Generator $faker, array $userIds, string $now): void
    {
        $templates = [
            'Annual Alumni Networking Night',
            'Tech Leadership Fireside Chat',
            'Career Fair — Engineering & IT',
            'Graduate Mentorship Kickoff',
            'Industry Panel: AI & Data Careers',
            'Homecoming Weekend Mixer',
            'Workshop: Building Your Personal Brand',
        ];

        for ($e = 0; $e < self::EVENT_PARTICIPATIONS; $e++) {
            $this->db->table('event_participations')->insert([
                'user_id' => $userIds[$e % count($userIds)],
                'event_name' => $faker->randomElement($templates),
                'event_date' => $faker->dateTimeBetween('-18 months', '+3 months')->format('Y-m-d'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
