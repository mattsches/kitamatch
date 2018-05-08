<?php
/*
 * This file is part of the KitaMatch app.
 *
 * (c) Sven Giegerich <sven.giegerich@mailbox.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 /*
 |--------------------------------------------------------------------------
 | Matching Controller
 |--------------------------------------------------------------------------
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\MatchRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ProgramController;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Matching;
use App\Applicant;
use App\Program;
use App\Preference;
use App\Traits\GetPreferences;
use App\Mail\ApplicantMatch;
use App\Mail\ProgramMatch;

/**
* This controller is responsible for the matching process: preperation, call and handling of the Matchingtools API.
*/
class MatchingController extends Controller
{
  //include the trait 'GetPreferences'
  use GetPreferences;

  /**
  * Create a new controller instance, handle authentication
  *
  * @return void
  */
  public function __construct() {
    $this->middleware('auth');
  }

  /**
  * Store a single matching entry with the current match status
  *
  * @param App\Http\Requests\MatchRequest $request request
  * @return App\Matching
  */
  public function store(Request $request) {
    $match = new Matching;
    $match->aid = $request->student;
    $match->pid = $request->college;
    $match->status = $request->status;
    $match->save();
    return $match;
  }

  /**
  * List all matchings in a view
  *
  * @param App\Http\Requests\MatchRequest $request request
  * @return view matching.all
  */
  public function all() {
    $matches = DB::table('matches')->whereIn('status', [31, 32])->get();
    foreach ($matches as $match) {
      $applicant = Applicant::where('aid', '=', $match->aid)->first();
      $match->applicant_name = $applicant->last_name . " " . $applicant->first_name;
      $program = Program::where('pid', '=', $match->pid)->first();
      $match->program_name = $program->name;
    }
    return view('matching.all', array('matches' => $matches));
  }

  /**
  * Main matching method. Calls the MatchingTools.com-API after prepareMatching(), handles the response and writes matchings to the database.
  * At the moment there is no handling for invalid feedback from the API-call like Error 500.
  *
  * @return view AdminController@index
  */
  public function findMatchings() {
    $Program = new Program;
    $Applicant = new Applicant;
    $Preference = new Preference;
    $Matching = new Matching;

    $input = $this->prepareMatching();

    if (!(strlen(json_encode($input))>0)) {
      return redirect()->action('AdminController@index');
    }
    print_r(json_encode($input));
    echo "<br><br><br><br><br><br>";

    //GuzzleHttp\Client
    /*$client = new Client();
    $response = $client->post('https://api.matchingtools.org/hri/demo?optimum=college-optimal',
      [
        'auth' => [
          'mannheim', 'Exc3llence!'
        ],
        'body' =>
          json_encode($input),
        'headers' => ['Accept' => 'application/json']
      ]);
    //status code: $response->getStatusCode();

    //write the matches
    $result = json_decode($response->getBody(), true);
    $matchingResult = $result['hri_matching'];

    print_r($result);

    //temp: set active = 0 for all previous entries != final
    $Matching->resetMatches();
    $Preference->resetUncoordinated();

    //store the positiv matches
    foreach ($matchingResult as $match) {
      $matchRequest = new Request();
      $matchRequest->setMethod('POST');
      $matchRequest->request->add(['college' => (int)$match['college'],
                                   'student' => (int)$match['student']
                                 ]);
      //check if it's the final match
      if ((int)$match['college'] == (int)$input['student_prefs'][(int)$match['student']][0]) {
        $matchRequest->request->add(['status' => 32]);
        $this->store($matchRequest);
        //set applicant status to matched
        app('App\Http\Controllers\ApplicantController')->setFinalMatch($match['student']);
      } else {
        $matchRequest->request->add(['status' => 31]);
        $this->store($matchRequest);
      }

      //check if program is uncoordinated
      $coordination = $Program->isCoordinated((int)$match['college']);
      if ($coordination == 0) {
        // if then update prefs back to 1
        $preferencesUncoordinated = $this->getPreferencesUncoordinatedByProgram((int)$match['college']);
        foreach ($preferencesUncoordinated as $preference) {
          //only for this specific match
          if ((int)$preference->id_to == (int)$match['student']) {
            $Preference->updateStatus($preference->prid, 1);
          }
        }
      }
    }*/
    //return redirect()->action('AdminController@index');
  }


  /**
  * Create the structure necessary for the API-Call of MatchingTools.com, for more see code and https://matchingtools.com/#operation/hri_demo
  *
  * @return array
  */
  public function prepareMatching() {
    //Format requirements: https://matchingtools.com/#operation/hri_demo
    $Preference = new Preference;
    $Applicant = new Applicant;
    $json = [];
    $preferencesApplicants = [];

    //create coordinated prefs
    app('App\Http\Controllers\PreferenceController')->createCoordinatedPreferences();
    //look for non active programs
    app('App\Http\Controllers\ProgramController')->setNonActive();

    //--------------------
    //by applicant
    $applicants = $Applicant->getAll();
    $programsC = DB::table('programs')
      //exclude status code 13: inactive for 7 days
      ->where('status', '=', 12)
      ->where('coordination', '=', 1)
      ->get();
    $programsU = DB::table('programs')
      ->where('status', '=', 12)
      ->where('coordination', '=', 0)
      ->get();

    foreach ($applicants as $applicant) {
      $preferencesByApplicant = $this->getPreferencesByApplicant($applicant->aid);
      $preferenceList = array();
      foreach ($preferencesByApplicant as $preference) {
        if ($programsC->contains('pid', $preference->id_to) OR
          $programsU->contains('pid', $preference->id_to)) {
          $preferenceList[] = (string)$preference->id_to;
        }
      }
      //check if there are any preferences
      if (count($preferenceList) > 0) {
        $preferencesApplicants[$applicant->aid] = $preferenceList;
      }
    }
    if (count($preferencesApplicants)>0) {
      $json["student_prefs"] = $preferencesApplicants;
    } else {
      //there are no valid students listed, so abort
      return;
    }

    //--------------------
    //capacity
    $capacityList = array();
    $Program = new program;
    //1: coordinated
    foreach ($programsC as $program) {
      if ($Preference->hasPreferencesByProgram($program->pid)) {
        $pid = (string)$program->pid;
        $capacity = app('App\Http\Controllers\ProgramController')->getCapacity($program->pid);
        if ($capacity > 0) {
          $capacityList[$pid] = $capacity;
        }
      }
    }
    //2: uncoordinated
    foreach ($programsU as $program) {
      if ($Preference->hasPreferencesByProgram($program->pid)) {
        $pid = (string)$program->pid;
        $capacity = app('App\Http\Controllers\ProgramController')->getCapacity($program->pid);
        if ($capacity > 0) {
          $capacityList[$pid] = $capacity;
        }
      }
    }
    $json["college_capacity"] = $capacityList;

    //--------------------
    //by program
    //1: only program that take part in the coordinated way
    foreach ($programsC as $program) {
      if (array_key_exists($program->pid, $capacityList)) {
        $preferencesByProgram = $this->getPreferencesByProgram($program->pid);
        $preferenceList = array();
        foreach ($preferencesByProgram as $preference) {
          $prefsApp = collect($preferencesApplicants);
          if ( $prefsApp->contains('id_to', $preference->id_from)  ) {
            $preferenceList[] = (string)$preference->id_to;
          }
        }
        //check if there are any preferences
        if (count($preferenceList) > 0) {
          $preferencesPrograms[$program->pid] = $preferenceList;
        }
      }
    }
    //2: add the programs that take the uncoordinated way
    foreach ($programsU as $program) {
      if (array_key_exists($program->pid, $capacityList)) {
        $preferencesByProgram = $this->getPreferencesUncoordinatedByProgram($program->pid);
        $preferenceList = array();
        foreach ($preferencesByProgram as $preference) {
          //list only active preferences
          if ($preference->status == 1) {
            $preferenceList[] = (string)$preference->id_to;
          }
        }
        //check if there are any preferences
        if (count($preferenceList) > 0) {
          $preferencesPrograms[$program->pid] = $preferenceList;
        }
      }
    }
    if (count($preferencesPrograms) > 0) {
      $json["college_prefs"] = $preferencesPrograms;
    } else {
      //there are no valid programs listed, so abort
      return;
    }

    return ($json);
  }

  /**
  * At the end of the matching procedure send mails to all existing matches
  *
  * @return void
  */
  public function sendMailsAllMatches() {
    $matches = Matching::whereIn('status', [31, 32])->get();
    //to guardian
    foreach($matches as $match) {
      //to guardian aka applicant
      $applicant = Guardian::where('aid', '=', $match->aid)->first();
      $guardian = Applicant::where('gid', '=', $applicant->aid)->first();
      $user = User::where('id', '=', $guardian->uid);
      Mail::to($user->email)->send(new ApplicantMatchMail($guardian));
    }
    //to programs
    //...
  }

}
