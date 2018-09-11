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
 | Matching Model
 |--------------------------------------------------------------------------
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

/**
* This model handles matches
*/
class Matching extends Model
{

  /**
  * Reset all  matches, except final ones, to historical status (before writing the new ones)
  *
  * @return void
  */
  public function resetMatches() {
    //right now: set all current matches on status = 33 before the new results
    //alternative: only update "new" or "different" matches and not all, but through his the matching history is lost
    $nonactive = DB::table('matches')
      ->where('status', '!=', 32)
      ->update(array('status' => 33));
  }

  public function lastMatch() {
    $lastMatch = DB::table('matches')
      ->orderBy('updated_at', 'desc')
      ->limit(1)
      ->first();
    if (!empty($lastMatch)) {
      return $lastMatch->updated_at;
    } else {
      return strtotime('01/01/2000');
    }
  }

  public function getMatchesByProgram($pid) {
    $matches = DB::table('matches')
      ->where('pid', '=', $pid)
      ->get();
    print_r($matches);
    return $matches;
  }

  public $primaryKey = 'mid';
  protected $table = 'matches';
}
