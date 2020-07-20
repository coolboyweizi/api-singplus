<?php

namespace SingPlus\Domains\Verifications\Repositories;

use Carbon\Carbon;
use SingPlus\Domains\Verifications\Models\Verification;

class VerificationRepository
{
  /**
   * @param string $mobile
   *
   * @return ?Verification
   */
  public function findLastRequested(string $mobile) : ?Verification
  {
      $verification = Verification::where('mobile', $mobile)        
                                  ->where('expired_at', '>=', Carbon::now()->format('Y-m-d H:i:s')) 
                                  ->whereNull('deleted_at')         
                                  ->orderBy('expired_at', 'desc')   
                                  ->first();                        
                                                                    
      return $verification; 
  }

  /**
   * Count how many verification after specified time
   *
   * @param string $mobile
   * @param ?string $time   format: Y-m-d H:i:s
   */
  public function countAfterTime(?string $mobile, string $time) : int
  {
    $query = Verification::where('created_at', '>=', $time);
    if ($mobile) {
      $query->where('mobile', $mobile);
    }

    return $query->count();
  }
}
