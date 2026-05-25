<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    'guest' => [
        'default_title' => 'Welcome',
    ],
    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'password_confirmation' => 'Confirm Password',
    ],
    'pages' => [
        'login' => [
            'title' => 'Log In',
            'remember' => 'Remember me',
            'forgot_password' => 'Forgot your password?',
            'submit' => 'Log In',
        ],
        'register' => [
            'title' => 'Register',
            'already_registered' => 'Already registered?',
            'submit' => 'Register',
        ],
        'forgot_password' => [
            'title' => 'Forgot Password',
            'description' => 'Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.',
            'submit' => 'Email Password Reset Link',
        ],
        'reset_password' => [
            'title' => 'Reset Password',
            'submit' => 'Reset Password',
        ],
        'confirm_password' => [
            'title' => 'Confirm Password',
            'description' => 'This is a secure area of the application. Please confirm your password before continuing.',
            'submit' => 'Confirm',
        ],
        'verify_email' => [
            'title' => 'Verify Email',
            'description' => 'Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.',
            'status' => 'A new verification link has been sent to the email address you provided during registration.',
            'resend' => 'Resend Verification Email',
            'logout' => 'Log Out',
        ],
    ],
    'social' => [
        'aria_label' => 'Social sign-in options',
        'continue_with' => 'Continue with :provider',
        'sign_up_with' => 'Sign up with :provider',
        'or_continue_with_email' => 'or continue with email',
        'or_sign_up_with_email' => 'or sign up with email',
        'providers' => [
            'google' => 'Google',
            'facebook' => 'Facebook',
        ],
        'errors' => [
            'not_configured' => ':provider login is not configured yet.',
            'unable_to_authenticate' => 'Unable to authenticate with :provider. Please try again.',
            'email_missing' => ':provider did not return an email address. Please register with email and password instead.',
        ],
    ],

];
