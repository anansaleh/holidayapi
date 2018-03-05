<?php

use Illuminate\Database\Seeder;

/**
 * Discription of the class
 *
 * @author anan
 */
class HolidaysTableSeeder extends Seeder
{
    /**
    * Run the database seeds.
    *
    * @return void
    */
    public function run()
    {
        // echo app_path();
        // https://github.com/martinjw/Holiday
        $data = file_get_contents('./data/US.json');
        $json = json_decode($data, true);

        foreach ($json as $holliday) {
            DB::table('holidays')->insert([
            'country_code' => 'US',
            'name' => $holliday['name'],
            'rule' => $holliday['rule'],
            ]);
        }
    }
}
