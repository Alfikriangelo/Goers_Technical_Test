<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\RestaurantSchedule;
use App\Services\ScheduleParserService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed a realistic weekly schedule for every restaurant.
 *
 * Each "hours" group is read as: days => service windows (24h clock), exactly
 * as the source list states them. Days that do not appear are closed. A service
 * whose close time is earlier than its open time crosses midnight, e.g.
 * 17:00-04:00.
 *
 * The source gives one continuous window per day group, so that is what is
 * stored: no artificial lunch break is inserted. Which meals a window covers
 * (breakfast, lunch, dinner, late night) is derived at read time by
 * ScheduleFormatterService, so the same row can be labelled "Lunch & dinner"
 * without splitting it. A small number of source edge cases (a closed weekday,
 * an after-midnight kitchen, a zero-length window) are made explicit.
 */
class RestaurantSeeder extends Seeder
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $catalog = [
        [
            'name' => 'Kushi Tsuru',
            'cuisine' => 'Japanese',
            'address' => '1737 Post St, San Francisco, CA',
            'rating' => 4.6,
            'hours' => [
                'Mon-Sun' => ['11:30-21:00'],
            ],
        ],
        [
            'name' => 'Osakaya Restaurant',
            'cuisine' => 'Japanese',
            'address' => 'Japan Center, San Francisco, CA',
            'rating' => 4.4,
            'hours' => [
                'Mon-Thu, Sun' => ['11:30-21:00'],
                'Fri-Sat' => ['11:30-21:30'],
            ],
        ],
        [
            'name' => 'The Stinking Rose',
            'cuisine' => 'Italian',
            'address' => '325 Columbus Ave, San Francisco, CA',
            'rating' => 4.5,
            'hours' => [
                'Mon-Thu, Sun' => ['11:30-22:00'],
                'Fri-Sat' => ['11:30-23:00'],
            ],
        ],
        [
            'name' => "McCormick & Kuleto's",
            'cuisine' => 'Seafood',
            'address' => '900 North Point St, San Francisco, CA',
            'rating' => 4.3,
            'hours' => [
                'Mon-Thu, Sun' => ['11:30-22:00'],
                'Fri-Sat' => ['11:30-23:00'],
            ],
        ],
        [
            'name' => 'Mifune Restaurant',
            'cuisine' => 'Japanese Noodle',
            'address' => '1737 Post St, San Francisco, CA',
            'rating' => 4.5,
            'hours' => [
                'Mon-Sun' => ['11:00-22:00'],
            ],
        ],
        [
            'name' => 'The Cheesecake Factory',
            'cuisine' => 'American',
            'address' => '251 Geary St 8th Fl, San Francisco, CA',
            'rating' => 4.2,
            'hours' => [
                'Mon-Thu' => ['11:00-23:00'],
                'Fri-Sat' => ['11:00-00:30'],
                'Sun' => ['10:00-23:00'],
            ],
        ],
        [
            'name' => 'New Delhi Indian Restaurant',
            'cuisine' => 'Indian',
            'address' => '160 Ellis St, San Francisco, CA',
            'rating' => 4.4,
            'hours' => [
                'Mon-Sat' => ['11:30-22:00'],
                'Sun' => ['17:30-22:00'],
            ],
        ],
        [
            'name' => 'Iroha Restaurant',
            'cuisine' => 'Japanese',
            'address' => '1728 Buchanan St, San Francisco, CA',
            'rating' => 4.3,
            'hours' => [
                'Mon-Thu, Sun' => ['11:30-21:30'],
                'Fri-Sat' => ['11:30-22:00'],
            ],
        ],
        [
            'name' => 'Rose Pistola',
            'cuisine' => 'Italian',
            'address' => '532 Columbus Ave, San Francisco, CA',
            'rating' => 4.1,
            'hours' => [
                'Mon-Thu' => ['11:30-22:00'],
                'Fri-Sun' => ['11:30-23:00'],
            ],
        ],
        [
            'name' => "Alioto's Restaurant",
            'cuisine' => 'Seafood',
            'address' => '8 Fishermans Wharf, San Francisco, CA',
            'rating' => 4.0,
            'hours' => [
                'Mon-Sun' => ['11:00-23:00'],
            ],
        ],
        [
            'name' => 'Canton Seafood & Dim Sum Restaurant',
            'cuisine' => 'Chinese / Dim Sum',
            'address' => '655 Folsom St, San Francisco, CA',
            'rating' => 4.2,
            'hours' => [
                'Mon-Fri' => ['10:30-21:30'],
                'Sat-Sun' => ['10:00-21:30'],
            ],
        ],
        [
            'name' => 'All Season Restaurant',
            'cuisine' => 'Chinese',
            'address' => '5016 Geary Blvd, San Francisco, CA',
            'rating' => 4.3,
            'hours' => [
                'Mon-Fri' => ['10:00-21:30'],
                'Sat-Sun' => ['09:30-21:30'],
            ],
        ],
        [
            'name' => 'Bombay Indian Restaurant',
            'cuisine' => 'Indian',
            'address' => '317 Columbus Ave, San Francisco, CA',
            'rating' => 4.5,
            'hours' => [
                'Mon-Sun' => ['11:30-22:30'],
            ],
        ],
        [
            'name' => "Sam's Grill & Seafood Restaurant",
            'cuisine' => 'Seafood',
            'address' => '374 Bush St, San Francisco, CA',
            'rating' => 4.6,
            'description' => 'Historic grill with weekday lunch and dinner; Saturday is dinner only and Sunday is closed.',
            'hours' => [
                'Mon-Fri' => ['11:00-21:00'],
                'Sat' => ['17:00-21:00'],
            ],
        ],
        [
            'name' => '2G Japanese Brasserie',
            'cuisine' => 'Japanese Fusion',
            'address' => '425 Market St, San Francisco, CA',
            'rating' => 4.4,
            'hours' => [
                'Mon-Thu, Sun' => ['11:00-22:00'],
                'Fri-Sat' => ['11:00-23:00'],
            ],
        ],
        [
            'name' => 'Restaurant Lulu',
            'cuisine' => 'Mediterranean',
            'address' => '816 Folsom St, San Francisco, CA',
            'rating' => 4.3,
            'hours' => [
                'Mon-Thu, Sun' => ['11:30-21:00'],
                'Fri-Sat' => ['11:30-22:00'],
            ],
        ],
        [
            'name' => 'Sudachi',
            'cuisine' => 'Sushi & Bar',
            'address' => '1217 Wildcat Way, San Francisco, CA',
            'rating' => 4.7,
            'description' => 'Dinner-only sushi bar with later service on Thursday through Saturday and an early Sunday close.',
            'hours' => [
                'Mon-Wed' => ['17:00-00:30'],
                'Thu-Fri' => ['17:00-01:30'],
                'Sat' => ['15:00-01:30'],
                'Sun' => ['15:00-23:30'],
            ],
        ],
        [
            'name' => 'Hanuri',
            'cuisine' => 'Korean',
            'address' => '4220 Geary Blvd, San Francisco, CA',
            'rating' => 4.4,
            'hours' => [
                'Mon-Sun' => ['11:00-00:00'],
            ],
        ],
        [
            'name' => 'Herbivore',
            'cuisine' => 'Vegan / Vegetarian',
            'address' => '983 Valencia St, San Francisco, CA',
            'rating' => 4.3,
            'hours' => [
                'Mon-Thu, Sun' => ['09:00-22:00'],
                'Fri-Sat' => ['09:00-23:00'],
            ],
        ],
        [
            'name' => 'Penang Garden',
            'cuisine' => 'Malaysian',
            'address' => '728 Washington St, San Francisco, CA',
            'rating' => 4.2,
            'hours' => [
                'Mon-Thu' => ['11:00-22:00'],
                'Fri-Sat' => ['10:00-22:30'],
                'Sun' => ['11:00-23:00'],
            ],
        ],
        [
            'name' => "John's Grill",
            'cuisine' => 'Steakhouse',
            'address' => '63 Ellis St, San Francisco, CA',
            'rating' => 4.5,
            'hours' => [
                'Mon-Sat' => ['11:00-22:00'],
                'Sun' => ['12:00-22:00'],
            ],
        ],
        [
            'name' => 'Quan Bac',
            'cuisine' => 'Vietnamese',
            'address' => '1311 Noriega St, San Francisco, CA',
            'rating' => 4.4,
            'hours' => [
                'Mon-Sun' => ['11:00-22:00'],
            ],
        ],
        [
            'name' => 'Bamboo Restaurant',
            'cuisine' => 'Asian Bistro',
            'address' => '400 W Portal Ave, San Francisco, CA',
            'rating' => 4.1,
            'hours' => [
                'Mon-Sat' => ['11:00-00:00'],
                'Sun' => ['12:00-00:00'],
            ],
        ],
        [
            'name' => 'Burger Bar',
            'cuisine' => 'Burgers',
            'address' => '251 Geary St, San Francisco, CA',
            'rating' => 4.2,
            'hours' => [
                'Mon-Thu, Sun' => ['11:00-22:00'],
                'Fri-Sat' => ['11:00-00:00'],
            ],
        ],
        [
            'name' => 'Blu Restaurant',
            'cuisine' => 'Contemporary American',
            'address' => '747 Market St, San Francisco, CA',
            'rating' => 4.3,
            'description' => 'Weekday lunch and dinner service, with weekend brunch only — the source closes at 3 pm on Saturday and Sunday.',
            'hours' => [
                'Mon-Fri' => ['11:30-22:00'],
                'Sat-Sun' => ['07:00-15:00'],
            ],
        ],
        [
            'name' => "Naan 'N' Curry",
            'cuisine' => 'Pakistani / Indian',
            'address' => "478 O'Farrell St, San Francisco, CA",
            'rating' => 4.6,
            'description' => 'Lunch and dinner service, with the dinner kitchen running until 4 am the following morning.',
            'hours' => [
                'Mon-Sun' => ['11:00-04:00'],
            ],
        ],
        [
            'name' => 'Shanghai China Restaurant',
            'cuisine' => 'Chinese',
            'address' => '631 Kearney St, San Francisco, CA',
            'rating' => 4.0,
            'hours' => [
                'Mon-Sun' => ['11:00-21:30'],
            ],
        ],
        [
            'name' => 'Tres',
            'cuisine' => 'Mexican',
            'address' => '130 Townsend St, San Francisco, CA',
            'rating' => 4.4,
            'hours' => [
                'Mon-Thu, Sun' => ['11:30-22:00'],
                'Fri-Sat' => ['11:30-23:00'],
            ],
        ],
        [
            'name' => 'Isobune Sushi',
            'cuisine' => 'Sushi',
            'address' => '1737 Post St, San Francisco, CA',
            'rating' => 4.1,
            'hours' => [
                'Mon-Sun' => ['11:30-21:30'],
            ],
        ],
        [
            'name' => 'Viva Pizza Restaurant',
            'cuisine' => 'Pizza & Italian',
            'address' => '1500 Powell St, San Francisco, CA',
            'rating' => 4.3,
            'hours' => [
                'Mon-Sun' => ['11:00-00:00'],
            ],
        ],
        [
            'name' => 'Far East Cafe',
            'cuisine' => 'Cantonese',
            'address' => '631 Grant Ave, San Francisco, CA',
            'rating' => 4.2,
            'hours' => [
                'Mon-Sun' => ['11:30-22:00'],
            ],
        ],
        [
            'name' => 'Bai Thong Thai Cuisine',
            'cuisine' => 'Thai',
            'address' => '298 9th St, San Francisco, CA',
            'rating' => 4.5,
            'hours' => [
                'Mon-Sat' => ['11:00-23:00'],
                'Sun' => ['11:00-22:00'],
            ],
        ],
        [
            'name' => 'Alhamra',
            'cuisine' => 'Halal Indian',
            'address' => '3096 16th St, San Francisco, CA',
            'rating' => 4.3,
            'hours' => [
                'Mon-Sun' => ['11:00-23:00'],
            ],
        ],
        [
            'name' => 'A-1 Cafe Restaurant',
            'cuisine' => 'Hong Kong Cafe',
            'address' => '2114 Clement St, San Francisco, CA',
            'rating' => 4.4,
            'description' => 'Closed on Tuesdays; lunch and dinner service every other day of the week.',
            'hours' => [
                'Mon, Wed-Sun' => ['11:00-22:00'],
            ],
        ],
        [
            'name' => "Nick's Lighthouse",
            'cuisine' => 'Seafood',
            'address' => '5 Fishermans Wharf, San Francisco, CA',
            'rating' => 4.0,
            'hours' => [
                'Mon-Sun' => ['11:00-22:30'],
            ],
        ],
        [
            'name' => 'Paragon Restaurant & Bar',
            'cuisine' => 'American Gastropub',
            'address' => '701 2nd St, San Francisco, CA',
            'rating' => 4.3,
            'description' => 'Weekday lunch and dinner, Saturday dinner only, and closed on Sunday.',
            'hours' => [
                'Mon-Fri' => ['11:30-22:00'],
                'Sat' => ['17:30-22:00'],
            ],
        ],
        [
            'name' => 'Chili Lemon Garlic',
            'cuisine' => 'Thai',
            'address' => '2010 Lombard St, San Francisco, CA',
            'rating' => 4.5,
            'hours' => [
                'Mon-Fri' => ['11:00-22:00'],
                'Sat-Sun' => ['17:00-22:00'],
            ],
        ],
        [
            'name' => 'Bow Hon Restaurant',
            'cuisine' => 'Cantonese Claypot',
            'address' => '850 Grant Ave, San Francisco, CA',
            'rating' => 4.2,
            'hours' => [
                'Mon-Sun' => ['11:00-22:30'],
            ],
        ],
        [
            'name' => 'San Dong House',
            'cuisine' => 'Northern Chinese',
            'address' => '5524 Geary Blvd, San Francisco, CA',
            'rating' => 4.1,
            'hours' => [
                'Mon-Sun' => ['11:00-23:00'],
            ],
        ],
        [
            'name' => 'Thai Stick Restaurant',
            'cuisine' => 'Thai',
            'address' => '698 Post St, San Francisco, CA',
            'rating' => 4.2,
            'description' => 'Lunch and dinner service, with the dinner kitchen open past midnight every night.',
            'hours' => [
                'Mon-Sun' => ['11:00-01:00'],
            ],
        ],
        [
            'name' => "Cesario's",
            'cuisine' => 'Italian',
            'address' => '601 Sutter St, San Francisco, CA',
            'rating' => 4.4,
            'hours' => [
                'Mon-Thu, Sun' => ['11:30-22:00'],
                'Fri-Sat' => ['11:30-22:30'],
            ],
        ],
        [
            'name' => 'Colombini Italian Cafe Bistro',
            'cuisine' => 'Italian Bistro',
            'address' => '2046 4th St, San Rafael, CA',
            'rating' => 4.3,
            'hours' => [
                'Mon-Fri' => ['12:00-22:00'],
                'Sat-Sun' => ['17:00-22:00'],
            ],
        ],
        [
            'name' => 'Sabella & La Torre',
            'cuisine' => 'Seafood',
            'address' => '2809 Taylor St, San Francisco, CA',
            'rating' => 4.1,
            'hours' => [
                'Mon-Thu, Sun' => ['10:00-22:30'],
                'Fri-Sat' => ['10:00-00:30'],
            ],
        ],
        [
            'name' => 'Soluna Cafe and Lounge',
            'cuisine' => 'California Cuisine',
            'address' => '272 McAllister St, San Francisco, CA',
            'rating' => 4.3,
            'description' => 'Weekday lunch and dinner, Saturday dinner only, and closed on Sunday.',
            'hours' => [
                'Mon-Fri' => ['11:30-22:00'],
                'Sat' => ['17:00-22:00'],
            ],
        ],
        [
            'name' => 'Tong Palace',
            'cuisine' => 'Dim Sum',
            'address' => '933 Clement St, San Francisco, CA',
            'rating' => 4.2,
            'hours' => [
                'Mon-Fri' => ['09:00-21:30'],
                'Sat-Sun' => ['09:00-22:00'],
            ],
        ],
        [
            'name' => 'India Garden Restaurant',
            'cuisine' => 'Indian',
            'address' => '2114 Polk St, San Francisco, CA',
            'rating' => 4.4,
            'hours' => [
                'Mon-Sun' => ['10:00-23:00'],
            ],
        ],
        [
            'name' => 'Sapporo-Ya Japanese Restaurant',
            'cuisine' => 'Japanese Ramen',
            'address' => '1581 Webster St, San Francisco, CA',
            'rating' => 4.4,
            'hours' => [
                'Mon-Sat' => ['11:00-23:00'],
                'Sun' => ['11:00-22:30'],
            ],
        ],
        [
            'name' => "Santorini's Mediterranean Cuisine",
            'cuisine' => 'Greek & Mediterranean',
            'address' => '2400 Mission St, San Francisco, CA',
            'rating' => 4.6,
            'hours' => [
                'Mon-Sun' => ['08:00-22:30'],
            ],
        ],
        [
            'name' => 'Kyoto Sushi',
            'cuisine' => 'Sushi',
            'address' => '2646 Ocean Ave, San Francisco, CA',
            'rating' => 4.5,
            'description' => 'Lunch and dinner on weekdays, a later Friday and Saturday service, and dinner only on Sunday.',
            'hours' => [
                'Mon-Thu' => ['11:00-22:30'],
                'Fri' => ['11:00-23:00'],
                'Sat' => ['11:30-23:00'],
                'Sun' => ['16:30-22:30'],
            ],
        ],
        [
            'name' => 'Marrakech Moroccan Restaurant',
            'cuisine' => 'Moroccan',
            'address' => "419 O'Farrell St, San Francisco, CA",
            'rating' => 4.7,
            'description' => 'Dinner-only restaurant with a kitchen that stays open until 2 am the next morning.',
            'hours' => [
                'Mon-Sun' => ['17:30-02:00'],
            ],
        ],
        [
            'name' => 'Parallel 37',
            'cuisine' => 'Fine Dining Lounge',
            'address' => '600 Stockton St, San Francisco, CA',
            'rating' => 4.8,
            'description' => 'Lounge and private-dining space with irregular event hours. The source listed a zero-length Tuesday window, which is treated as closed.',
            'hours' => [
                'Mon, Fri' => ['17:00-18:15'],
                'Wed' => ['13:15-17:45'],
                'Thu, Sat' => ['10:00-15:00'],
                'Sun' => ['06:30-12:45'],
            ],
        ],
    ];

    public function run(): void
    {
        $parser = new ScheduleParserService();

        DB::transaction(function () use ($parser) {
            $this->truncateRestaurants();

            foreach ($this->catalog as $entry) {
                $restaurant = Restaurant::create([
                    'name'         => $entry['name'],
                    'cuisine_type' => $entry['cuisine'],
                    'address'      => $entry['address'],
                    'rating'       => $entry['rating'],
                    'description'  => $entry['description']
                        ?? "Authentic {$entry['cuisine']} restaurant in San Francisco offering delicious dishes and great dining ambiance.",
                ]);

                $seen = [];

                foreach ($entry['hours'] as $daySpec => $services) {
                    foreach ($parser->parseDaysPart($daySpec) as $day) {
                        foreach ($services as $service) {
                            [$open, $close] = $this->parseService($service);

                            // A service with identical start and end has no
                            // duration; treat it as a closed day instead of
                            // storing a row that can never match a query.
                            if ($open === $close) {
                                continue;
                            }

                            $key = "{$day}|{$open}|{$close}";
                            if (isset($seen[$key])) {
                                continue;
                            }
                            $seen[$key] = true;

                            $restaurant->schedules()->create([
                                'day_of_week'    => $day,
                                'open_time'      => $open,
                                'close_time'     => $close,
                                'spans_next_day' => $close < $open,
                            ]);
                        }
                    }
                }
            }
        });
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseService(string $service): array
    {
        if (!preg_match('/^(\d{1,2}:\d{2})-(\d{1,2}:\d{2})$/', trim($service), $matches)) {
            throw new \InvalidArgumentException("Invalid seeded service window: '{$service}'");
        }

        return [
            Carbon::createFromFormat('H:i', $matches[1])->format('H:i:s'),
            Carbon::createFromFormat('H:i', $matches[2])->format('H:i:s'),
        ];
    }

    /**
     * Empty both tables and restart identities.
     *
     * PostgreSQL refuses a plain TRUNCATE on a table that is referenced by a
     * foreign key unless every referencing table is truncated in the same
     * statement, so both tables go together there.
     */
    private function truncateRestaurants(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('TRUNCATE TABLE restaurant_schedules, restaurants RESTART IDENTITY CASCADE');

            return;
        }

        RestaurantSchedule::truncate();
        Restaurant::truncate();
    }
}
