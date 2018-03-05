<?php

namespace App\Http\Controllers;

use App\Holiday;
use Illuminate\Http\Request;
use App\Libraries\Holidays;
use App\Libraries\HolidaysApi;
use App\Libraries\Exception;

/**
 * Class HolidaysController
 * @package App\Http\Controllers
 *
 * @author anan
 */
class HolidaysController
{
    /**
     *
     * GET /holidays
     * @return array
     */
    public function index(Request $request, $country = null, $year = null)
    {
        // Check country and year
        if (is_null($country)) {
            $country = $request->country;
        }

        if (is_null($country) || empty(trim($country))) {
            return response()->json([
                'status' => 403,
                'message' => 'Forbidden Request',
                'error' => ['message' => 'The country parameter is required.']
            ], 403);
        }

        if (is_null($year)) {
            $year = $request->year;
        }

        if (is_null($year) || !is_numeric($year) || strlen((string) $year) != 4) {
            return response()->json([
                'status' => 403,
                'message' => 'Forbidden Request',
                'error' => ['message' => 'The year parameter is required and must be 4 degits.']
            ], 403);
        }
        $options = [];
        if ($request->has('month')) {
            $options['month'] = str_pad($request->month, 2, '0', STR_PAD_LEFT);
        }
        if ($request->has('day')) {
            $options['day'] = str_pad($request->day, 2, '0', STR_PAD_LEFT);
        }

        if ($request->has('previous')) {
            $options['previous'] = true;
        }
        if ($request->has('upcoming')) {
            $options['upcoming'] = true;
        }
        if ($request->has('public')) {
            $options['public'] = true;
        }
        $options['cache'] = true;

        try {
            $holidays = new HolidaysApi($country, $year, $options);
            $return = $holidays->getHolidays();
            // $return = $holidays->calculateHolidays(true);

            // Status
            // 200: The request has succeeded. Content was found
            // 204: The request has succeeded. No Content was found
            $status = (count($return) > 0) ? 200 : 204;
            $message = (count($return) > 0) ? 'succeeded with content' : 'succeeded with no content.';
            // echo '<pre>';
            // print_r($return);
            // echo '</pre>';
            // // dump($return);
            // die();
            return response()->json([
                'status' => $status,
                'message' => $message,
                'holidays' => $return
                ], 200);
        } catch (Exception\MissingParametersException $e) {
            // 403 FORBIDDEN: The request has been refused.
            return response()->json([
                'status' => 403,
                'message' => 'Forbidden Request',
                'error' => ['message' => $e->getMessage()]
              ], 403);
        } catch (Exception\InvalidParameterException $e) {
            // 406 NOT ACCEPTABLE: The request specified an invalid format.
            return response()->json([
                'status' => 406,
                'message' => 'Not Acceptable Request',
                'error' => ['message' => $e->getMessage()]
              ], 406);
        } catch (\Exception $e) {
            // 400 BAD REQUEST: The request was invalid or cannot be otherwise served.
            return response()->json([
                'status' => 400,
                'message' => 'Bad Request',
                'error' => ['message' => $e->getMessage()]
              ], 400);
        }
    }
}
