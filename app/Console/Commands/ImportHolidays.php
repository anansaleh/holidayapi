<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportHolidays extends Command
{
    /**
     * The directory name where the data json files are stored
     * 
     * @var string
     */
    private $dir = './storage/data';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:holidays {--country=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command read country_code (ISO Alpha-2) from user input,
                              search file with [country_code].json "./storage/data" path with holidays rules,  
                                and then import data from json file into Database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        // $this->line('Some text');
        // $this->info('Hey, watch this !');
        // $this->comment('Just a comment passing by');
        // $this->question('Why did you do that?');
        // $this->error('Ops, that should not happen.');
        // $this->line('------------------');

        $country = strtoupper($this->option('country'));
        if (!$country) {
            $files[] = 'ALL';
            foreach (glob($this->dir . '/*.json') as $file) {
                $files[] = pathinfo($file)['filename'];
            }
            $country = $this->choice('chose counter code?', $files, 0);
        }

        switch ($country) {
            case 'ALL':
                foreach (glob($this->dir . '/*.json') as $file) {
                    // $files[] = pathinfo($file)['filename'];
                    $this->importDataFromJsonFile(strtoupper(pathinfo($file)['filename']), $file);
                }
                break;
            default:
                $file = $this->dir . '/' . $country . '.json';
                if (!file_exists($file)) {
                    $this->error('file ' . $file . ' not exists');
                } else {
                    $this->importDataFromJsonFile($country, $file);
                }
        }
    }

    /**
     * Read a json file with holidays rule, insert the rules into Data base 
     * and then move the json file into backups folder.
     *
     * @param string $country: uppercase(ISO Alpha-2 country code)
     * @param string $file_name: json file name with full path
     * 
     * @return void
     */
    private function importDataFromJsonFile($country, $file_name)
    {
        $data = file_get_contents($file_name);
        $json = json_decode($data, true);

        foreach ($json as $holliday) {
            $tmp = [
                'country_code' => $country,
                'name' => $holliday['name'],
                'rule' => $holliday['rule'],
            ];

            if (!$this->isRecordExists($tmp)) {
                DB::table('holidays')->insert([
                    'country_code' => $tmp['country_code'],
                    'name' => $tmp['name'],
                    'rule' => $tmp['rule']
                    ]);
            } else {
                $this->info('Data exist');
                print_r($tmp);
            }
        }

        //Set general public holidays
        $this->updatePublicHoliday($country);

        // Move the Json file to backup folder
        $destination = $this->dir . '/backups/';
        $date = date('Y-m-d');
        $rename_file = $destination . $country . '_' . $date . '.json';
        rename($file_name, $rename_file);

    }

    /**
     * Check if array record is exists in DB holidays table
     * 
     * @param array $data: record fields 
     * 
     * @return boolean if data is exists in DB
     */
    private function isRecordExists($data)
    {
        $row = DB::table('holidays')
                ->where('country_code', $data['country_code'])
                ->where('name', $data['name'])
                ->where('rule', $data['rule'])
                ->get()
                ->toArray();
        return (count($row) > 0);
    }

    /**
     * Update the DB holidays table with most general public holidays rules
     * 
     * @param string $country: uppercase(ISO Alpha-2 country code)
     * 
     * @return void
     */
    private function updatePublicHoliday($country)
    {
        $public_rules = [
            'January 1st', '%EASTER', '%EASTER -2 days', '%EASTER +1 day', 'May 1st',
            'December 25th', 'December 26th', 'December 31st', '24 December %Y +1 weekday', '24 December %Y +2 weekday',

        ];
        DB::table('holidays')
            ->where('country_code', $country)
            ->whereIn('rule', $public_rules)
            ->update(['public' => 1]);
    }
}
