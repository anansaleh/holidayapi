<?php


class RouteTest extends TestCase
{
  /**
   * @test
   */
  public function status_code_should_be_200()
  {
    $this->get('/')->seeStatusCode(200);
    $this->get('/holidays/us/2018')->seeStatusCode(200);
    $this->get('/holidays/?country=us&year=2018')->seeStatusCode(200);
  }

  /**
   * Not found route: This test for Route when url not found
   * 
   * @test
   */
  public function status_code_should_be_404()
  {
    $this->get('/anyUrlString')->seeStatusCode(404);
    $this->get('/holidays/us')->seeStatusCode(404);
    $this
      ->get('/anyUrlString')
      ->seeJson( [
        'status'=> 404,
        'message'=>'NOT FOUND',
        'error' => ['message' =>'Sorry, the page you are looking for could not be found.']
      ] );
  }

}
