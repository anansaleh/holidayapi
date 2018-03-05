<?php

use Illuminate\Http\Response;

class HolidaysControllerTest extends TestCase
{
  /**
   * Request is succeeded
   * @test
   */
  public function index_status_code_should_be_200()
  {
    $this->get('/holidays/us/2018')->seeStatusCode(200);
    $this->get('/holidays/us/2018/?month=5')->seeStatusCode(200);
    $this->get('/holidays/us/2018/?month=1&day=1')->seeStatusCode(200);


    $this->get('/holidays/?country=us&year=2018')->seeStatusCode(200);
    $this->get('/holidays/?country=us&year=2018&?month=5')->seeStatusCode(200);
    $this->get('/holidays/?country=us&year=2018&month=12&day=24')->seeStatusCode(200);

    $this->get('/holidays/?country=us&year=2018&month=12&day=24&upcoming=pp')->seeStatusCode(200);
    $this->get('/holidays/?country=us&year=2018&month=12&day=24&previous=pp')->seeStatusCode(200);

    $this->get('/holidays/us/2018/?public=sds')->seeStatusCode(200);
    $this->get('/holidays/?country=us&year=2018&public=sdsd')->seeStatusCode(200);
  }


  /**
   * Succeeded with no collection
   * @test
   */
  public function index_should_not_return_any_collection()
  {
    $this->get('/holidays/gr/2018')->seeStatusCode(200);
    $this
      ->get('/holidays/gr/2018')
      ->seeJson( [
        'status'=> 204,
        'message'=>'succeeded with no content.',
        'holidays' => []
      ] );
    $this->get('/holidays/?country=gr&year=2018')->seeStatusCode(200);
    $this
      ->get('/holidays/?country=gr&year=2018')
      ->seeJson( [
        'status'=> 204,
        'message'=>'succeeded with no content.',
        'holidays' => []
      ] );
  }

  /**
   * Succeeded with a collection
   * 
   * @test
   */
  public function index_should_return_a_collection_of_records()
  {
    // by year collection
    $this->get('/holidays/us/2018/');
    $body = json_decode($this->response->getContent(), true );
    $this->assertArrayHasKey('holidays', $body);
    $this->assertCount(47, $body['holidays']);
    $this->assertArrayHasKey('2018-02-19', $body['holidays']);

    $arr= $body['holidays']['2018-02-19'];
    $this->assertCount(1, $arr);
    $this->assertEquals( $arr[0]['name'], 'Washington\'s Birthday');
    $this->assertEquals( $arr[0]['country'], 'us');
    $this->assertEquals( $arr[0]['date'], '2018-02-19');
    $this->assertEquals( $arr[0]['public'], '0');

    ///////////////////////////////
    // public
    $this->get('/holidays/us/2018/?public=1');
    $body = json_decode($this->response->getContent(), true );
    $this->assertArrayHasKey('holidays', $body);
    $this->assertCount(7, $body['holidays']);
    $this->assertArrayHasKey('2018-01-01', $body['holidays']);

    $arr= $body['holidays']['2018-01-01'];
    $this->assertCount(2, $arr);
    $this->assertEquals( $arr[1]['name'], 'New Year\'s Day');
    $this->assertEquals( $arr[1]['country'], 'us');
    $this->assertEquals( $arr[1]['date'], '2018-01-01');
    $this->assertEquals( $arr[1]['public'], '1');

    ///////////////////////
    // month
    $this->get('/holidays/us/2018/?month=3')
      ->seeJson( [
        'status'=> 200,
        'message'=>'succeeded with content',
        'holidays'=>[
              [
                'name'=>'International Women\'s Day',
                'country'=> 'us',
                'date'=>'2018-03-08',
                'public'=> 0
              ],
              [
                'name'=>'Saint Patrick\'s Day',
                'country'=> 'us',
                'date'=>'2018-03-17',
                'public'=> 0
              ],
              [
                'name'=>'Palm Sunday',
                'country'=> 'us',
                'date'=>'2018-03-25',
                'public'=> 0
              ],
              [
                'name'=>'Good Friday',
                'country'=> 'us',
                'date'=>'2018-03-30',
                'public'=> 1
              ]
          ]
      ] );
  }

  /**
   * Succeeded with a collection
   * 
   * @test
   */
  public function index_should_return_single_record()
  {
    // Single day
    $this->get('/holidays/us/2018/?month=3&day=25')
    ->seeJson( [
      'status'=> 200,
      'message'=>'succeeded with content',
      'holidays'=>[
          [
            'name'=>'Palm Sunday',
            'country'=> 'us',
            'date'=>'2018-03-25',
            'public'=> 0
          ]
        ]
    ] );

    // previous Single day
    $this->get('/holidays/us/2018/?month=3&day=25&previous=1')
    ->seeJson( [
      'status'=> 200,
      'message'=>'succeeded with content',
      'holidays'=>[
          [
            'name'=>'Saint Patrick\'s Day',
            'country'=> 'us',
            'date'=>'2018-03-17',
            'public'=> 0
          ],
        ]
    ] );

    // upcoming Single day
    $this->get('/holidays/us/2018/?month=3&day=25&upcoming=1')
    ->seeJson( [
      'status'=> 200,
      'message'=>'succeeded with content',
      'holidays'=>[
          [
            'name'=>'Good Friday',
            'country'=> 'us',
            'date'=>'2018-03-30',
            'public'=> 1
          ],
        ]
    ] );
  }
}
