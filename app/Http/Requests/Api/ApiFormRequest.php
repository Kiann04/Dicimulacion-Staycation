<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base for every v1 API request.
 *
 * Validation failures are rendered centrally in bootstrap/app.php, which keys on
 * the api/* path rather than on the Accept header. That way a client that forgot
 * to send "Accept: application/json" still receives the documented JSON 422
 * instead of an HTML redirect, and inline $request->validate() calls produce the
 * identical envelope.
 */
abstract class ApiFormRequest extends FormRequest {}
