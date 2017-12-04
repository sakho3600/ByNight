<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 04/12/2017
 * Time: 22:39
 */

namespace App\Social;


use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SocialProvider
{
    const FACEBOOK = 'facebook';
    const FACEBOOK_LIST_EVENTS = 'facebook_list_events';
    const FACEBOOK_EVENTS = 'facebook_events';
    const FACEBOOK_ADMIN = 'facebook_admin';
    const TWITTER = 'twitter';
    const GOOGLE = 'google';

    /**
     * @var array
     */
    private $socials;

    public function __construct(Facebook $facebook, FacebookEvents $facebookEvents, FacebookListEvents $facebookListEvents, FacebookAdmin $facebookAdmin, Twitter $twitter, Google $google)
    {
        $this->socials = [
            self::FACEBOOK => $facebook,
            self::FACEBOOK_EVENTS => $facebookEvents,
            self::FACEBOOK_LIST_EVENTS => $facebookListEvents,
            self::FACEBOOK_ADMIN => $facebookAdmin,
            self::TWITTER => $twitter,
            self::GOOGLE => $google
        ];
    }

    /**
     * @param $name
     * @param string $default_facebook_name
     * @return Social
     */
    public function getSocial($name, $default_facebook_name = self::FACEBOOK): Social {
        if($name === self::FACEBOOK) {
            $name = $default_facebook_name;
        }

        if(! isset($this->socials[$name])) {
            throw new NotFoundHttpException(sprintf('Unable to find social service with id "%s"', $name));
        }

        return $this->socials[$name];
    }
}