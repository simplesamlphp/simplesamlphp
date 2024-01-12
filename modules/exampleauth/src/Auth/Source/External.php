<?php

declare(strict_types=1);

namespace SimpleSAML\Module\exampleauth\Auth\Source;

use SimpleSAML\{Auth, Error, Module, Utils};
use Symfony\Component\HttpFoundation\{Request, Response};

use function session_id;
use function session_start;

/**
 * Example external authentication source.
 *
 * This class is an example authentication source which is designed to
 * hook into an external authentication system.
 *
 * To adapt this to your own web site, you should:
 * 1. Create your own module directory.
 * 2. Enable to module in the config by adding '<module-dir>' => true to the $config['module.enable'] array.
 * 3. Copy this file to its corresponding location in the new module.
 * 4. Replace all occurrences of "exampleauth" in this file with the name of your module.
 * 5. Adapt the getUser()-function, the authenticate()-function and the logout()-function to your site.
 * 6. Add an entry in config/authsources.php referencing your module. E.g.:
 *        'myauth' => [
 *            '<mymodule>:External',
 *        ],
 *
 * @package SimpleSAMLphp
 */
class External extends Auth\Source
{
    /**
     * The key of the AuthId field in the state.
     */
    public const AUTHID = 'SimpleSAML\Module\exampleauth\Auth\Source\External.AuthId';


    /**
     * Constructor for this authentication source.
     *
     * @param array $info  Information about this authentication source.
     * @param array $config  Configuration.
     */
    public function __construct(array $info, array $config)
    {
        // Call the parent constructor first, as required by the interface
        parent::__construct($info, $config);

        // Do any other configuration we need here
    }


    /**
     * Retrieve attributes for the user.
     *
     * @return array|null  The user's attributes, or NULL if the user isn't authenticated.
     */
    private function getUser(): ?array
    {
        /*
         * In this example we assume that the attributes are
         * stored in the users PHP session, but this could be replaced
         * with anything.
         */

        if (!session_id()) {
            // session_start not called before. Do it here
            @session_start();
        }

        if (!isset($_SESSION['uid'])) {
            // The user isn't authenticated
            return null;
        }

        /*
         * Find the attributes for the user.
         * Note that all attributes in SimpleSAMLphp are multivalued, so we need
         * to store them as arrays.
         */

        $attributes = [
            'uid' => [$_SESSION['uid']],
            'displayName' => [$_SESSION['name']],
            'mail' => [$_SESSION['mail']],
        ];

        // Here we generate a multivalued attribute based on the account type
        $attributes['eduPersonAffiliation'] = [
            $_SESSION['type'], /* In this example, either 'student' or 'employee'. */
            'member',
        ];

        return $attributes;
    }


    /**
     * Log in using an external authentication helper.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request  The current request
     * @param array &$state  Information about the current authentication.
     */
    public function authenticate(Request $request, array &$state): ?Response
    {
        $attributes = $this->getUser();
        if ($attributes !== null) {
            /*
             * The user is already authenticated.
             *
             * Add the users attributes to the $state-array, and return control
             * to the authentication process.
             */
            $state['Attributes'] = $attributes;
            return null;
        }

        /*
         * The user isn't authenticated. We therefore need to
         * send the user to the login page.
         */

        /*
         * First we add the identifier of this authentication source
         * to the state array, so that we know where to resume.
         */
        $state['exampleauth:AuthID'] = $this->authId;

        /*
         * We need to save the $state-array, so that we can resume the
         * login process after authentication.
         *
         * Note the second parameter to the saveState-function. This is a
         * unique identifier for where the state was saved, and must be used
         * again when we retrieve the state.
         *
         * The reason for it is to prevent
         * attacks where the user takes a $state-array saved in one location
         * and restores it in another location, and thus bypasses steps in
         * the authentication process.
         */
        $stateId = Auth\State::saveState($state, 'exampleauth:External');

        /*
         * Now we generate a URL the user should return to after authentication.
         * We assume that whatever authentication page we send the user to has an
         * option to return the user to a specific page afterwards.
         */
        $returnTo = Module::getModuleURL('exampleauth/resume', [
            'AuthState' => $stateId,
        ]);

        /*
         * Get the URL of the authentication page.
         *
         * Here we use the getModuleURL function again, since the authentication page
         * is also part of this module, but in a real example, this would likely be
         * the absolute URL of the login page for the site.
         */
        $authPage = Module::getModuleURL('exampleauth/authpage');

        /*
         * The redirect to the authentication page.
         *
         * Note the 'ReturnTo' parameter. This must most likely be replaced with
         * the real name of the parameter for the login page.
         */
        $httpUtils = new Utils\HTTP();
        return $httpUtils->redirectTrustedURL($authPage, [
            'ReturnTo' => $returnTo,
        ]);
    }


    /**
     * Resume authentication process.
     *
     * This function resumes the authentication process after the user has
     * entered his or her credentials.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @throws \SimpleSAML\Error\BadRequest
     * @throws \SimpleSAML\Error\Exception
     */
    public static function resume(Request $request, Auth\State $authState): Response
    {
        /*
         * First we need to restore the $state-array. We should have the identifier for
         * it in the 'State' request parameter.
         */
        if (!$request->query->has('AuthState')) {
            throw new Error\BadRequest('Missing "AuthState" parameter.');
        }

        /*
         * Once again, note the second parameter to the loadState function. This must
         * match the string we used in the saveState-call above.
         */
        $state = $authState::loadState($request->query->get('AuthState'), 'exampleauth:External');

        /*
         * Now we have the $state-array, and can use it to locate the authentication
         * source.
         */
        $source = Auth\Source::getById($state['exampleauth:AuthID']);
        if ($source === null) {
            /*
             * The only way this should fail is if we remove or rename the authentication source
             * while the user is at the login page.
             */
            throw new Error\Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
        }

        /*
         * Make sure that we haven't switched the source type while the
         * user was at the authentication page. This can only happen if we
         * change config/authsources.php while an user is logging in.
         */
        if (!($source instanceof self)) {
            throw new Error\Exception('Authentication source type changed.');
        }

        /*
         * OK, now we know that our current state is sane. Time to actually log the user in.
         *
         * First we check that the user is actually logged in, and didn't simply skip the login page.
         */
        $attributes = $source->getUser();
        if ($attributes === null) {
            /*
             * The user isn't authenticated.
             *
             * Here we simply throw an exception, but we could also redirect the user back to the
             * login page.
             */
            throw new Error\Exception('User not authenticated after login page.');
        }

        /*
         * So, we have a valid user. Time to resume the authentication process where we
         * paused it in the authenticate()-function above.
         */

        $state['Attributes'] = $attributes;
        return parent::completeAuth($state);
    }


    /**
     * This function is called when the user start a logout operation, for example
     * by logging out of a SP that supports single logout.
     *
     * @param array &$state  The logout state array.
     */
    public function logout(array &$state): ?Response
    {
        if (!session_id()) {
            // session_start not called before. Do it here
            @session_start();
        }

        /*
         * In this example we simply remove the 'uid' from the session.
         */
        unset($_SESSION['uid']);

        /*
         * If we need to do a redirect to a different page, we could do this
         * here, but in this example we don't need to do this.
         */
        return null;
    }
}
