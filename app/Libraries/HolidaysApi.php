<?php

namespace App\Libraries;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Holiday;

/**
 * This class implement of the functionalities as in https://bitbucket.org/croudtech/holidayapi with more change.
 * it's throw two type Exception InvalidParameterException and MissingParametersException
 *
 * @author anan
 */
class HolidaysApi
{
    /**
     * var string
     */
    private $country;

    /**
     * var integr
     */
    private $year ;

    /**
     * var integr
     */
    private $month ;

    /**
     * var integr
     */
    private $day ;

    /**
     * var boolean
     */
    private $previous = false;

    /**
     * var boolean
     */
    private $upcoming = false;

    /**
     * var boolean
     */
    private $public = false;

    /**
     * var array
     */
    private $country_holidays;

    /**
     * var boolean
     */
    private $cache = false;

    /**
     * var integr
     */
    private $cache_minutes = 1440; // 24 hours

    /**
     *
     * @param string $country: ISO Alpha-2 country code
     * @param integer $year
     * @param array $args optional
     *    $args[
     *        "month"         : integer [optional]
     *        "day"           : integer [optional]
     *        "previous"      : [optional] if is set then true
     *        "upcoming"      : [optional] if is set then true
     *        "public"        : [optional] if is set then true, false: will show all
     *        "cache"         : [optional] if set then true
     *        "cache_minutes" : integer [optional]
     *        ]
     * 
     * @return void
     * @throw App\Libraries\Exception\MissingParametersException
     * @throw App\Libraries\Exception\InvalidParameterException
     */
    public function __construct($country, $year, $args = [])
    {
        // Cache::flush();

        // Check country and year
        if (is_null($country) || empty(trim($country))) {
            throw new Exception\MissingParametersException('The country parameter is required.');
        }

        if (is_null($year) || !is_numeric($year) || strlen((string) $year) !== 4) {
            throw new Exception\InvalidParameterException('The year parameter is required and must be 4 degits.');
        }

        // fill the other arguments;
        $this->country = $country;
        $this->year = $year;
        $this->country_holidays = [];
         $payload = [];

        $this->month = isset($args['month']) ? str_pad($args['month'], 2, '0', STR_PAD_LEFT) : '';
        $this->day = isset($args['day']) ? str_pad($args['day'], 2, '0', STR_PAD_LEFT) : '';

        $this->previous       = isset($args['previous']);
        $this->upcoming       = isset($args['upcoming']);
        $this->public         = isset($args['public']);

        $this->cache          = isset($args['cache']);
        $this->cache_minutes  = isset($args['cache_minutes']) ? intval($args['cache_minutes']): 1440;

        // Validate
        if ($this->previous && $this->upcoming) {
            throw new Exception\InvalidParameterException('You cannot request both previous and upcoming holidays.');
        } elseif (($this->previous || $this->upcoming) && (!$this->month || !$this->day)) {
            $request = $this->previous ? 'previous' : 'upcoming';
            $missing = empty($this->month) ? 'month' : 'day';
            throw new Exception\MissingParametersException('The ' . $missing . ' parameter is required when requesting ' . $request . ' holidays.');
        }
        if (!empty(trim($this->month)) ){
            if (!is_numeric($this->month)){
                throw new Exception\InvalidParameterException('The supplied month (' . $this->month . ') is invalid.');
            }
        }
        if (!empty(trim($this->day)) ){
            if (!is_numeric($this->day)){
                throw new Exception\InvalidParameterException('The supplied day (' . $this->day . ') is invalid.');
            }
        }

        $date = $this->year . '-' . $this->month . '-' . $this->day;
        if (!empty(trim($this->month)) && !empty(trim($this->day))) {
            if (strtotime($date) === false) {
                throw new Exception\InvalidParameterException('The supplied date (' . $date . ') is invalid.');
            }
        }

        $this->country_holidays = $this->calculateHolidays(($this->previous || $this->upcoming));
    }

    /**
     * return array hollidays from cache or calculate the rules in DB
     * 
     * @param boolean $range if true then return array holidays with previous, current and next years else only the current year
     * @return array holidays 
     */
    private function calculateHolidays($range = false)
    {
        $return = [];

        //Prepare the range years
        if ($range) {
            $years = [$this->year - 1, $this->year, $this->year + 1];
        } else {
            $years = [$this->year];
        }

        foreach ($years as $year) {
            // $calculated_holidays = [];

            if ($this->cache) {
                $cache_key = 'holidayapi:' . $this->country . ':holidays:' . $this->year;
                //Cache::forget($cache_key);
                $country_holidays = Cache::remember($cache_key, $this->cache_minutes, function () use ($year) {
                    return $this->getHolidaysOfYear($year);
                    
                });

                //clear cache if no data are found to get fresh data at thesecond time
                if (count($country_holidays) == 0) {
                    Cache::forget($cache_key);
                }
            } else {
                $country_holidays = $this->getHolidaysOfYear($year);
            }
            
            // Filter Holidays by public
            if ($this->public){
                $public = $this->public;
                $holidays=[];
                // dump($country_holidays);
                foreach($country_holidays as $key=>$holiday){
                    // dump($key);
                    $holiday= array_values(array_filter($holiday, function($elem) use($public){
                        return $elem['public'] == $public;
                    }) );
                    // dump($holiday[$key]);
                    if(count($holiday) > 0) {
                        $holidays[$key]=$holiday;
                    }
                    
                }
            } else {
                $holidays = $country_holidays;
            }
            
            $return[$year] = $holidays;
        }
        // dump($return);
        return $return;
    }

    /**
     * getHolidaysOfYear: get the rules from the DB and calculate the holidays of target year
     * 
     * @param integer year
     * @return array holiday of target year
     */
    private function getHolidaysOfYear($year)
    {
        $holiday_rules = $this->getHolidaysDataRules();

        //Check data
        if (count($holiday_rules) == 0) {
            return [];
        }
        $calculated_holidays = [];

        foreach ($holiday_rules as $holiday_rule){
          if (strstr($holiday_rule['rule'], '%Y')) {
            $rule = str_replace('%Y', $year, $holiday_rule['rule']);
          } elseif (strstr($holiday_rule['rule'], '%EASTER')) {
            $rule = str_replace('%EASTER', date('Y-m-d', strtotime($year . '-03-21 +' . easter_days($year) . ' days')), $holiday_rule['rule']);
          }  elseif (in_array($this->country, ['BR', 'US']) && strstr($holiday_rule['rule'], '%ELECTION')) {
            switch ($this->country) {
              case 'BR':
                $years = range(2014, $year, 2);
                break;
              case 'US':
                $years = range(1788, $year, 4);
                break;
            }
            if (in_array($year, $years)) {
              $rule = str_replace('%ELECTION', $year, $holiday_rule['rule']);
            } else {
              $rule = false;
            }
          } else {
            $rule = $holiday_rule['rule'] . ' ' . $year;
          }

          if ($rule) {
            $calculated_date = date('Y-m-d', strtotime($rule));
            if (!isset($calculated_holidays[$calculated_date])) {
              $calculated_holidays[$calculated_date] = [];
            }

            $calculated_holidays[$calculated_date][] = [
              'name'    => $holiday_rule['name'],
              'country' => $this->country,
              'date'    => $calculated_date,
              'public'  => $holiday_rule['public'],
            ];
          }
        }
        
        // Sort holidays by date
        ksort($calculated_holidays);
        foreach ($calculated_holidays as $date_key => $date_holidays) {
            usort($date_holidays, function ($a, $b) {
                $a = $a['name'];
                $b = $b['name'];

                if ($a == $b) {
                    return 0;
                }
                return $a < $b ? -1 : 1;
            });

            $calculated_holidays[$date_key] = $date_holidays;
        }

        return $calculated_holidays;
    }

    /**
     * @return array with holidays rules from DB
     */
    private function getHolidaysDataRules()
    {
        return Holiday::
            where('country_code', $this->country)
            ->get()
            ->toArray();
    }

    /**
     * @return array holidays
     */
    private function flatten($date, $array1, $array2)
    {
        $holidays = array_merge($array1, $array2);

        // Injects the current date as a placeholder
        if (!isset($holidays[$date])) {
            $holidays[$date] = false;
            ksort($holidays);
        }

        // Sets the internal pointer to today
        while (key($holidays) !== $date) {
            next($holidays);
        }

        return $holidays;
    }

    /**
     *
     * @return array holiday
     */
    public function getHolidays()
    {
         $payload = [];
        if (ini_get('date.timezone') == '') {
            date_default_timezone_set('UTC');
        }

        if (count($this->country_holidays) == 0) {
            return [];
        }

        $date = $this->year . '-' . $this->month . '-' . $this->day;
        if (strtotime($date) == true) {
            if ($this->previous) {
                $this->country_holidays = $this->flatten($date, $this->country_holidays[$this->year - 1], $this->country_holidays[$this->year]);
                prev($this->country_holidays);
                 $payload = current($this->country_holidays);
            } elseif ($this->upcoming) {
                $this->country_holidays = $this->flatten($date, $this->country_holidays[$this->year], $this->country_holidays[$this->year + 1]);
                next($this->country_holidays);
                 $payload = current($this->country_holidays);
            } elseif (isset($this->country_holidays[$this->year][$date])) {
                $payload = $this->country_holidays[$this->year][$date];
            }
        } elseif (!empty($this->month)) {
            foreach ($this->country_holidays[$this->year] as $date => $country_holiday) {
                if (substr($date, 0, 7) == $this->year . '-' . $this->month) {
                     $payload = array_merge( $payload, $country_holiday);
                }
            }
        } else {
             $payload = $this->country_holidays[$this->year];
        }


        // dump( $payload);
        // die();
        return  $payload;
    }
}
