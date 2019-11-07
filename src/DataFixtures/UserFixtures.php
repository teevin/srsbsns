<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\User;

class UserFixtures extends Fixture
{
    private $params;
    private $passwordEncoder;

    public function __construct(ContainerBagInterface $params, UserPasswordEncoderInterface $passwordEncoder)
    {
      $this->params = $params;
      $this->passwordEncoder = $passwordEncoder;
    }
    public function load(ObjectManager $manager)
    {
        $app_email = $this->params->get('app.admin_email');
        $app_pass = $this->params->get('app.admin_pass');
        $role = [$this->params->get('app.admin_role')];
        $app_token = $this->params->get('app.admin_token');

        $user = new User();
        $user->setEmail($app_email);
        $user->setPassword($this->passwordEncoder->encodePassword($user, $app_pass));
        $user->setRoles($role);
        $user->setApiToken($app_token);

        $manager->persist($user);
        $manager->flush();
    }
}
