<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;

/**
 * Le socle des contrôleurs du back-office : qui agit.
 *
 * La garde est **nommée** à chaque fois. Sans elle, `$request->user()` lirait
 * la garde par défaut — celle des voyageurs — et un geste du back-office
 * pourrait se retrouver signé par un compte client.
 */
abstract class OfficeController extends Controller
{
    protected function admin(Request $request): Admin
    {
        return $request->user('admin');
    }
}
