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
 | Preference Trait
 |--------------------------------------------------------------------------
 */

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use App\Criterium;
use App\Guardian;
use App\Applicant;


/**
* Preference trait for programs and applicants
*/
trait GetPreferences {

  /**
  * Get all active preferences of an applicant
  *
  * @param integer $aid Applicant-ID
  * @return Illuminate\Database\Eloquent\Collection preferences
  */
  public function getPreferencesByApplicant($aid) {
    $preferences = DB::table('preferences')->where('id_from', '=', $aid)
      ->whereIn('pr_kind', [0])
      ->where('status', '=', 1)
      ->orderBy('rank', 'asc')
      ->get();
      /*$sql = "SELECT * FROM preferences WHERE (`id_from` = " . $aid . " AND `status` = 1 AND (`pr_kind` = 1 OR `pr_kind` = 4)) ORDER BY rank asc, RAND()";
      $preferences = DB::select($sql);*/
      return $preferences;
  }

  /**
  * Get all active preferences of a coordinated program
  *
  * @param integer $pid Program-ID
  * @return Illuminate\Database\Eloquent\Collection preferences
  */
  public function getPreferencesByProgram($pid) {
    /*$preferences = DB::table('preferences')->where('id_from', '=', $pid)
      ->where('status', '=', 1)
      ->where('pr_kind', '=', 2)
      ->orderBy('rank', 'asc')
      ->get();*/
    $sql = "SELECT * FROM preferences WHERE `id_from` LIKE '" . $pid . "\\_%' AND `status` = 1 AND `pr_kind` = 2) ORDER BY rank asc, RAND()";
    $preferences = DB::select($sql);
    return $preferences;
  }

  /**
  * Get all active preferences of an uncoordinated program
  *
  * @param integer $pid Program-ID
  * @return Illuminate\Database\Eloquent\Collection preferences
  */
  public function getPreferencesUncoordinatedByProgram($pid) {
    /*$preferences = DB::table('preferences')->where('id_from', '=', $pid)
      ->whereIn('status', [1, -1])
      ->where('pr_kind', '=', 3)
      ->orderBy('rank', 'asc')
      ->get();*/
    //tmp: issue if all offers with rank = 1 and so ordered by time
    $sql = "SELECT * FROM preferences WHERE (`id_from` LIKE '" . $pid . "\\_%' AND (`status` = 1 OR `status` = -1) AND `pr_kind` = 3) ORDER BY rank asc, RAND()";
    $preferences = DB::select($sql);
    return $preferences;
  }

  /**
  * Get all non-active preferences of a program (coord or uncoord)
  *
  * @return Illuminate\Database\Eloquent\Collection preferences
  */
  public function getNonActivePreferencesByProgram() {
    $sql = "SELECT ANY_VALUE(`id_from`), ANY_VALUE(`pr_kind`), ANY_VALUE(`updated_at`), ANY_VALUE(`updated_at`), ANY_VALUE(`status`) FROM preferences WHERE (pr_kind = 2 OR OR pr_kind = 3) AND DATE(updated_at) < DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY id_from";
    $preferences = DB::select($sql);
    return $preferences;
  }

  public function getManualRankingsByProgram($pid) {
     return DB::table('preferences')->where('pr_kind', '=', '3')->where('id_from', 'LIKE', $pid . '\\_%')->where('status', '=', -3)->get();
  }
}
