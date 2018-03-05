<?php

class HolidaysControllerExeptionsTest extends TestCase
{
  /**
   * Forbidden Request: This test check when country or year parametr is missed or invalid
   * 
   * @test
   */
  public function index_status_code_should_be_403()
  {
    // When no paramers or country paramer is missed
    $this->get('/holidays')->seeStatusCode(403);
    $this
      ->get('/holidays')
      ->seeJson( [
        'status'=> 403,
        'message'=>'Forbidden Request',
        'error' => ['message' =>'The country parameter is required.']
      ] );
    
    // Country paramer is missed
    $this->get('/holidays/?year=2018')->seeStatusCode(403);    
    $this
      ->get('/holidays')
      ->seeJson( [
        'status'=> 403,
        'message'=>'Forbidden Request',
        'error' => ['message' =>'The country parameter is required.']
      ] );

    // When year parametrs is missed or when it's not 4 degits or not number
    $this->get('/holidays/?country=us/year=18')->seeStatusCode(403);
    $this->get('/holidays/?country=us/year=sdas')->seeStatusCode(403);
    $this
      ->get('/holidays/?country=us')
      ->seeJson( [
        'status'=> 403,
        'message'=>'Forbidden Request',
        'error' => ['message' =>'The year parameter is required and must be 4 degits.']
      ] );
    
    
    $this->get('/holidays/us/21')->seeStatusCode(403);
    $this
      ->get('/holidays/?country=us')
      ->seeJson( [
        'status'=> 403,
        'message'=>'Forbidden Request',
        'error' => ['message' =>'The year parameter is required and must be 4 degits.']
      ] );
  }

  /**
   * Not Acceptable Request: This test when parameter is invalid
   * 
   * @test
   */
  public function index_status_code_should_be_406()
  {
    // When month is not number
    $this->get('/holidays/us/2018/?month=kk')->seeStatusCode(406);    
    $this->get('/holidays/?country=us&year=2018&month=kk')->seeStatusCode(406);
    $this
      ->get('/holidays/us/2018/?month=kk')
      ->seeJson( [
        'status'=> 406,
        'message'=>'Not Acceptable Request',
        'error' => ['message' =>'The supplied month (kk) is invalid.']
      ] );

    // // When day is not number
    $this->get('/holidays/us/2018/?day=kk')->seeStatusCode(406);    
    $this->get('/holidays/?country=us&year=2018&day=kk')->seeStatusCode(406);
    $this
      ->get('/holidays/us/2018/?day=kk')
      ->seeJson( [
        'status'=> 406,
        'message'=>'Not Acceptable Request',
        'error' => ['message' =>'The supplied day (kk) is invalid.']
      ] );

    //when month and day are not invalid
    $this->get('/holidays/us/2018/?month=21&day=39')->seeStatusCode(406);    
    $this->get('/holidays/?country=us&year=2018&month=21&day=39')->seeStatusCode(406);
    $this
      ->get('/holidays/us/2018/?month=21&day=39')
      ->seeJson( [
        'status'=> 406,
        'message'=>'Not Acceptable Request',
        'error' => ['message' =>'The supplied date (2018-21-39) is invalid.']
      ] );

    //when we pass previous & upcoming together
    $this->get('/holidays/us/2018/?month=21&day=39&previous=pp&upcoming=pp')->seeStatusCode(406);    
    $this->get('/holidays/?country=us&year=2018&month=21&day=39&previous=pp&upcoming=pp')->seeStatusCode(406);
    $this
      ->get('/holidays/us/2018/?month=21&day=39&previous=pp&upcoming=pp')
      ->seeJson( [
        'status'=> 406,
        'message'=>'Not Acceptable Request',
        'error' => ['message' =>'You cannot request both previous and upcoming holidays.']
      ] );
  }
}