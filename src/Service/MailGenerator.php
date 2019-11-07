<?php
namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\NamedAddress;
use Symfony\Component\Mailer\MailerInterface;
use App\Entity\Contact;
use App\Entity\User;

class MailGenerator
{
  private $logger;
  private $htppClient;
  private $params;
  private $emailer;

  public function __construct(LoggerInterface $logger, HttpClientInterface $httpClient, ContainerBagInterface $params, MailerInterface $emailer)
  {
    $this->logger = $logger;
    $this->httpClient = $httpClient;
    $this->params = $params;
    $this->emailer = $emailer;
  }

  public function createMail(Contact $contact, User $user)
  {
    $app_email = $this->params->get('app.admin_email');
    $email = (new TemplatedEmail())
      ->from($app_email ?: 'tinashe.vdesign@gmail.com')
      ->to($contact->getEmailAddress() ?: new Address('tinashe.vdesign@gmail.com'))
      ->cc($user->getEmail() ?: 'tinashe.vdesign@gmail.com')
      ->subject('Thanks for contacting us!')
      // path of the Twig template to render
      ->htmlTemplate('email/email.html.twig')
      // pass variables (name => value) to the template
      ->context([
        'firstName' => $contact->getFirstName(),
        'lastName' => $contact->getLastName(),
        'message' => $contact->getMessage(),
      ]);
     $this->emailer->send($email);
  }

  public function verifyRecapture($token, $secret_key = NULL)
  {
    if (!$secret_key) {
      $secret_key  = $this->params->get('app.google_secret_key');
    }
    $response  = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
      'body' => [
        'secret' => $secret_key,
        'response' => $token,
      ],
    ]);
    $content = $response->toArray();
    if (isset($content['success']) ) {
      $content['success'] ? $this->logger->info('recaptcha validated') : $this->logger->error('recaptcha error',$content['error-codes']);
      return $content['success'];
    }
  }
}
