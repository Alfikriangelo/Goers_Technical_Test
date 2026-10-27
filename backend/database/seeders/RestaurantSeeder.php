<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\RestaurantSchedule;
use App\Services\ScheduleParserService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RestaurantSeeder extends Seeder
{
    private array $dataset = [
        ["Kushi Tsuru", "Mon-Sun 11:30 am - 9 pm", "Japanese", "1737 Post St, San Francisco, CA", 4.6],
        ["Osakaya Restaurant", "Mon-Thu, Sun 11:30 am - 9 pm / Fri-Sat 11:30 am - 9:30 pm", "Japanese", "Japan Center, San Francisco, CA", 4.4],
        ["The Stinking Rose", "Mon-Thu, Sun 11:30 am - 10 pm / Fri-Sat 11:30 am - 11 pm", "Italian", "325 Columbus Ave, San Francisco, CA", 4.5],
        ["McCormick & Kuleto's", "Mon-Thu, Sun 11:30 am - 10 pm / Fri-Sat 11:30 am - 11 pm", "Seafood", "900 North Point St, San Francisco, CA", 4.3],
        ["Mifune Restaurant", "Mon-Sun 11 am - 10 pm", "Japanese Noodle", "1737 Post St, San Francisco, CA", 4.5],
        ["The Cheesecake Factory", "Mon-Thu 11 am - 11 pm / Fri-Sat 11 am - 12:30 am / Sun 10 am - 11 pm", "American", "251 Geary St 8th Fl, San Francisco, CA", 4.2],
        ["New Delhi Indian Restaurant", "Mon-Sat 11:30 am - 10 pm / Sun 5:30 pm - 10 pm", "Indian", "160 Ellis St, San Francisco, CA", 4.4],
        ["Iroha Restaurant", "Mon-Thu, Sun 11:30 am - 9:30 pm / Fri-Sat 11:30 am - 10 pm", "Japanese", "1728 Buchanan St, San Francisco, CA", 4.3],
        ["Rose Pistola", "Mon-Thu 11:30 am - 10 pm / Fri-Sun 11:30 am - 11 pm", "Italian", "532 Columbus Ave, San Francisco, CA", 4.1],
        ["Alioto's Restaurant", "Mon-Sun 11 am - 11 pm", "Seafood", "8 Fishermans Wharf, San Francisco, CA", 4.0],
        ["Canton Seafood & Dim Sum Restaurant", "Mon-Fri 10:30 am - 9:30 pm / Sat-Sun 10 am - 9:30 pm", "Chinese / Dim Sum", "655 Folsom St, San Francisco, CA", 4.2],
        ["All Season Restaurant", "Mon-Fri 10 am - 9:30 pm / Sat-Sun 9:30 am - 9:30 pm", "Chinese", "5016 Geary Blvd, San Francisco, CA", 4.3],
        ["Bombay Indian Restaurant", "Mon-Sun 11:30 am - 10:30 pm", "Indian", "317 Columbus Ave, San Francisco, CA", 4.5],
        ["Sam's Grill & Seafood Restaurant", "Mon-Fri 11 am - 9 pm / Sat 5 pm - 9 pm", "Seafood", "374 Bush St, San Francisco, CA", 4.6],
        ["2G Japanese Brasserie", "Mon-Thu, Sun 11 am - 10 pm / Fri-Sat 11 am - 11 pm", "Japanese Fusion", "425 Market St, San Francisco, CA", 4.4],
        ["Restaurant Lulu", "Mon-Thu, Sun 11:30 am - 9 pm / Fri-Sat 11:30 am - 10 pm", "Mediterranean", "816 Folsom St, San Francisco, CA", 4.3],
        ["Sudachi", "Mon-Wed 5 pm - 12:30 am / Thu-Fri 5 pm - 1:30 am / Sat 3 pm - 1:30 am / Sun 3 pm - 11:30 pm", "Sushi & Bar", "1217 Wildcat Way, San Francisco, CA", 4.7],
        ["Hanuri", "Mon-Sun 11 am - 12 am", "Korean", "4220 Geary Blvd, San Francisco, CA", 4.4],
        ["Herbivore", "Mon-Thu, Sun 9 am - 10 pm / Fri-Sat 9 am - 11 pm", "Vegan / Vegetarian", "983 Valencia St, San Francisco, CA", 4.3],
        ["Penang Garden", "Mon-Thu 11 am - 10 pm / Fri-Sat 10 am - 10:30 pm / Sun 11 am - 11 pm", "Malaysian", "728 Washington St, San Francisco, CA", 4.2],
        ["John's Grill", "Mon-Sat 11 am - 10 pm / Sun 12 pm - 10 pm", "Steakhouse", "63 Ellis St, San Francisco, CA", 4.5],
        ["Quan Bac", "Mon-Sun 11 am - 10 pm", "Vietnamese", "1311 Noriega St, San Francisco, CA", 4.4],
        ["Bamboo Restaurant", "Mon-Sat 11 am - 12 am / Sun 12 pm - 12 am", "Asian Bistro", "400 W Portal Ave, San Francisco, CA", 4.1],
        ["Burger Bar", "Mon-Thu, Sun 11 am - 10 pm / Fri-Sat 11 am - 12 am", "Burgers", "251 Geary St, San Francisco, CA", 4.2],
        ["Blu Restaurant", "Mon-Fri 11:30 am - 10 pm / Sat-Sun 7 am - 3 pm", "Contemporary American", "747 Market St, San Francisco, CA", 4.3],
        ["Naan 'N' Curry", "Mon-Sun 11 am - 4 am", "Pakistani / Indian", "478 O'Farrell St, San Francisco, CA", 4.6],
        ["Shanghai China Restaurant", "Mon-Sun 11 am - 9:30 pm", "Chinese", "631 Kearney St, San Francisco, CA", 4.0],
        ["Tres", "Mon-Thu, Sun 11:30 am - 10 pm / Fri-Sat 11:30 am - 11 pm", "Mexican", "130 Townsend St, San Francisco, CA", 4.4],
        ["Isobune Sushi", "Mon-Sun 11:30 am - 9:30 pm", "Sushi", "1737 Post St, San Francisco, CA", 4.1],
        ["Viva Pizza Restaurant", "Mon-Sun 11 am - 12 am", "Pizza & Italian", "1500 Powell St, San Francisco, CA", 4.3],
        ["Far East Cafe", "Mon-Sun 11:30 am - 10 pm", "Cantonese", "631 Grant Ave, San Francisco, CA", 4.2],
        ["Parallel 37", "Mon-Sun 11:30 am - 10 pm", "Fine Dining", "600 Stockton St, San Francisco, CA", 4.7],
        ["Bai Thong Thai Cuisine", "Mon-Sat 11 am - 11 pm / Sun 11 am - 10 pm", "Thai", "298 9th St, San Francisco, CA", 4.5],
        ["Alhamra", "Mon-Sun 11 am - 11 pm", "Halal Indian", "3096 16th St, San Francisco, CA", 4.3],
        ["A-1 Cafe Restaurant", "Mon, Wed-Sun 11 am - 10 pm", "Hong Kong Cafe", "2114 Clement St, San Francisco, CA", 4.4],
        ["Nick's Lighthouse", "Mon-Sun 11 am - 10:30 pm", "Seafood", "5 Fishermans Wharf, San Francisco, CA", 4.0],
        ["Paragon Restaurant & Bar", "Mon-Fri 11:30 am - 10 pm / Sat 5:30 pm - 10 pm", "American Gastropub", "701 2nd St, San Francisco, CA", 4.3],
        ["Chili Lemon Garlic", "Mon-Fri 11 am - 10 pm / Sat-Sun 5 pm - 10 pm", "Thai", "2010 Lombard St, San Francisco, CA", 4.5],
        ["Bow Hon Restaurant", "Mon-Sun 11 am - 10:30 pm", "Cantonese Claypot", "850 Grant Ave, San Francisco, CA", 4.2],
        ["San Dong House", "Mon-Sun 11 am - 11 pm", "Northern Chinese", "5524 Geary Blvd, San Francisco, CA", 4.1],
        ["Thai Stick Restaurant", "Mon-Sun 11 am - 1 am", "Thai", "698 Post St, San Francisco, CA", 4.2],
        ["Cesario's", "Mon-Thu, Sun 11:30 am - 10 pm / Fri-Sat 11:30 am - 10:30 pm", "Italian", "601 Sutter St, San Francisco, CA", 4.4],
        ["Colombini Italian Cafe Bistro", "Mon-Fri 12 pm - 10 pm / Sat-Sun 5 pm - 10 pm", "Italian Bistro", "2046 4th St, San Rafael, CA", 4.3],
        ["Sabella & La Torre", "Mon-Thu, Sun 10 am - 10:30 pm / Fri-Sat 10 am - 12:30 am", "Seafood", "2809 Taylor St, San Francisco, CA", 4.1],
        ["Soluna Cafe and Lounge", "Mon-Fri 11:30 am - 10 pm / Sat 5 pm - 10 pm", "California Cuisine", "272 McAllister St, San Francisco, CA", 4.3],
        ["Tong Palace", "Mon-Fri 9 am - 9:30 pm / Sat-Sun 9 am - 10 pm", "Dim Sum", "933 Clement St, San Francisco, CA", 4.2],
        ["India Garden Restaurant", "Mon-Sun 10 am - 11 pm", "Indian", "2114 Polk St, San Francisco, CA", 4.4],
        ["Sapporo-Ya Japanese Restaurant", "Mon-Sat 11 am - 11 pm / Sun 11 am - 10:30 pm", "Japanese Ramen", "1581 Webster St, San Francisco, CA", 4.4],
        ["Santorini's Mediterranean Cuisine", "Mon-Sun 8 am - 10:30 pm", "Greek & Mediterranean", "2400 Mission St, San Francisco, CA", 4.6],
        ["Kyoto Sushi", "Mon-Thu 11 am - 10:30 pm / Fri 11 am - 11 pm / Sat 11:30 am - 11 pm / Sun 4:30 pm - 10:30 pm", "Sushi", "2646 Ocean Ave, San Francisco, CA", 4.5],
        ["Marrakech Moroccan Restaurant", "Mon-Sun 5:30 pm - 2 am", "Moroccan", "419 O'Farrell St, San Francisco, CA", 4.7],
        ["Parallel 37 (Lounge Edition)", "Mon, Fri 5 pm - 6:15 pm / Tues 12:15 pm - 12:15 pm / Weds 1:15 pm - 5:45 pm / Thurs, Sat 10 am - 3 pm / Sun 6:30 am - 12:45 pm", "Fine Dining Lounge", "600 Stockton St Ste 2, San Francisco, CA", 4.8],
    ];

    public function run(): void
    {
        $parser = new ScheduleParserService();

        DB::transaction(function () use ($parser) {
            RestaurantSchedule::truncate();
            Restaurant::truncate();

            foreach ($this->dataset as $entry) {
                [$name, $rawSchedule, $cuisine, $address, $rating] = $entry;

                $restaurant = Restaurant::create([
                    'name'         => $name,
                    'raw_schedule' => $rawSchedule,
                    'cuisine_type' => $cuisine,
                    'address'      => $address,
                    'rating'       => $rating,
                    'description'  => "Authentic {$cuisine} restaurant in San Francisco offering delicious dishes and great dining ambiance.",
                ]);

                $parsedShifts = $parser->parse($rawSchedule);
                foreach ($parsedShifts as $shift) {
                    $restaurant->schedules()->create($shift);
                }
            }
        });
    }
}
