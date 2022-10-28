<?php

namespace duxphp\DuxravelInstaller\Controllers;

use Illuminate\Routing\Controller;

class WelcomeController extends Controller
{
    /**
     * Display the installer welcome page.
     *
     * @return \Illuminate\Http\Response
     */
    public function welcome()
    {
        $content = file_get_contents(base_path('LICENSE'));
        return view('vendor/haibase/hairavel-installer/src/Views/welcome', [
            'content' => $content
        ]);
    }
}
