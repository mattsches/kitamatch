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
 | Preference Model
 |--------------------------------------------------------------------------
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
* This model handles the preferences of programs and applicants
*/
class Preference extends Model
{
  //pr_kind: 1:applicant, 2:program coordinated, 3:program uncoordinated

  /**
  * Update the status of a single preference
  *
  * @param integer $prid Preference-ID
  * @param integer $status status
  * @return void
  */
  public function updateStatus($prid, $status) {
    $exec = DB::table('preferences')
      ->where('prid', '=', $prid)
      ->update(array('status' => $status));
  }

  /**
  * Update the rank of a single preference
  *
  * @param integer $prid Preference-ID
  * @param integer $rank rank
  * @return void
  */
  public function updateRank($prid, $rank) {
    $exec = DB::table('preferences')
      ->where('prid', '=', $prid)
      ->update(array('rank' => $rank));
  }

  /**
  * Reset (status = -1) all offers (rank = 1) created by uncoordianted programs. This method is called during the findMatchings() process, to differ between successfull and denied offers
  *
  * @return void
  */
  public function resetUncoordinatedOffers() {
    $nonactive = DB::table('preferences')
      ->where('pr_kind', 3)
      ->where('rank', '=', 1)
      ->join('applicants', function ($join) {
        $join->on('preferences.id_to', '=', 'applicants.aid')
        ->whereIn('applicants.status', [22, 25]);
      })
      ->update(array('preferences.status' => -1));
  }

  public function resetAllUncoordnatedQueuesByApplicant($aid, $pid) {
    $nonactive = DB::table('preferences')
      ->where('pr_kind', '=', 3)
      ->where('rank', '>', 1)
      ->where('id_to', '=', $aid)
      ->where('id_from', '!=', $pid)
      ->update(array('status' => -1));
  }

  /**
  * Does the program has any active (status = 1) preferences?
  *
  * @param integer $pid Program-ID
  * @return boolean
  */
  public function hasPreferencesByProgram($pid) {
    $preferenceProgram = Preference::where('id_from', '=', $pid)
      ->where('status', '>', 0)
      ->whereIn('pr_kind', [2, 3])
      ->first();
    if ($preferenceProgram === null) {
      return false;
    } else {
      return true;
    }
  }

  /**
  * Does the applicant has any active (status = 1) preferences?
  *
  * @param integer $aid Applicant-ID
  * @param boolean
  */
  public function hasPreferencesByApplicant($aid) {
    $preferenceApplicant = Preference::where('id_from', '=', $aid)
      ->where('status', '>', 0)
      ->where('pr_kind', 1)
      ->first();
    if ($preferenceApplicant === null) {
      return 0;
    } else {
      return 1;
    }
  }

  /**
  * Get all applicants that are available for a program (because the applicant has ranked this program)
  *
  * @param integer $pid Program-ID
  * @return Illuminate\Database\Eloquent\Collection applicants
  */
  public function getAvailableApplicants($pid) {
    /*
    SELECT applicants.* FROM preferences
    INNER JOIN applicants ON applicants.aid = preferences.id_from
    WHERE preferences.id_to = <*6*>
    WHERE preferences.pr_kind = 1
    ORDER BY preferences.status
    */

    $applicants = DB::table('preferences')
      ->join('applicants', 'applicants.aid', '=', 'preferences.id_from')
      ->where('preferences.id_to', '=', $pid)
      //tmp: status?
      ->where('preferences.pr_kind', 1)
      ->select('applicants.*')
      ->get();
    return $applicants;
  }

  /**
  * Takes an array of applicants and sorts them after the corresponding criteria catalogue of the provider or program.
  * Adds an additional order attribute to every entry containing the criteria score
  *
  * @param App\Applicant $applicants applicants
  * @param integer $p_ID Program/Provider-ID
  * @param boolean $provider IsProvider?
  * @return Illuminate\Database\Eloquent\Collection applicants ordered, and with order-attribute (correspoonding criteria-points)
  */
  public function orderByCriteria($applicants, $p_Id, $provider) {
    //$provider = true -> criteria from a provider level
    if ($provider) {
      $criteria = Criterium::where('p_id', '=', $p_Id)
        ->orderBy('rank', 'asc')
        ->get();
    } else {
      //single program
      $criteria = Criterium::where('p_id', '=', $p_Id)
        ->where('program', '=', 1)
        ->orderBy('rank', 'asc')
        ->get();
    }

    //if criteria is null, use the default order (indicated by providerId = -1)
    if ($criteria) {
      $criteria = Criterium::where('p_id', '=', -1)
      ->orderBy('rank', 'asc')
      ->get();
    }

    foreach($applicants as $applicant) {
      $guardian = Guardian::find($applicant->gid);
      $applicant->order = 0;
      if ($guardian != null) {
        foreach($criteria as $criterium) {
          $criterium_name = $criterium->criterium_name;
          if ($criterium->criterium_value == $guardian->{$criterium_name}) {
            $applicant->order = $applicant->order + $criterium->rank * $criterium->multiplier;
          }
        }
      } else {
        //no guardian -> order = 10000, to order asc
        $applicant->order = 0;
      }
      //highly important applicants
      if ($applicant->status == 25) {
        $applicant->order = 2 ** 12;
      }
    }

    //tie braker, sort by birthday on the same level
    //https://github.com/laravel/ideas/issues/11;
    $applicants = $applicants->sort(function($a, $b) {
      if($a->order === $b->order) {
        if($a->birthday === $b->birthday) {
          return 0;
         }
        return $a->birthday < $b->birthday ? +1 : -1;
      }
      return $a->order < $b->order ? +1 : -1;
    });
    dd($applicants);
    return $applicants;
  }

  /**
  * Get the lowest preference rank of an applicant.
  * For example applicant 1 has ranked (1,2,3,4), the lowest rank would be 4.
  *
  * @param integer $aid Applicant-ID
  * @return integer
  */
  public function getLowestRankApplicant($aid) {
    $sql = "SELECT rank FROM preferences WHERE id_from = " . $aid . " AND (pr_kind = 1) ORDER BY rank DESC LIMIT 1";
    $lowestRank = DB::select($sql);
    if (count($lowestRank) > 0) {
      $rank = $lowestRank['0']->rank;
    } else {
      $rank = 1;
    }
    return $rank;
  }

  /**
  * Get the lowest preference rank of a program.
  *
  * @param integer $pid Program-ID
  * @return integer
  */
  public function getLowestRankUncoordinatedProgram($pid) {
    $sql = "SELECT rank FROM preferences WHERE id_from = " . $pid . " AND (pr_kind = 3) ORDER BY rank DESC LIMIT 1";
    $lowestRank = DB::select($sql);
    if (count($lowestRank) > 0) {
      $rank = $lowestRank['0']->rank;
    } else {
      $rank = 1;
    }
    return $rank;
  }

  public $primaryKey = 'prid';
}
