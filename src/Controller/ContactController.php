<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Contact;
use App\Entity\User;
use App\Form\ContactType;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Service\MailGenerator;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ContactController extends AbstractController
{
    /**
     * @Route("/", name="contact_form")
     */
    public function index(Request $request, MailGenerator $mailer_service)
    {
      $contact = new Contact();
      $form = $this->createForm(ContactType::class, $contact);
      $form->handleRequest($request);
      $recaptcha  = FALSE;
      if ($form->isSubmitted()) {
        $recaptcha_value = $request->request->get('g-recaptcha-response');
        $recaptcha = $mailer_service->verifyRecapture($recaptcha_value);
      }
      if ($form->isSubmitted() && $form->isValid() && $recaptcha) {
        $contact = $form->getData();
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($contact);
        $entityManager->flush();
        $admin_user = $this->getAdminUser();
        $res = $mailer_service->createMail($contact,$admin_user);
        return $this->redirectToRoute('thank_you');
      }

      if (!$recaptcha) {
        $this->addFlash(
          'error',
          'Google Recaptcha Error'
        );
      }

      return $this->render('contact.html.twig', [
        'form' => $form->createView(),
      ]);
    }
    /**
     * @Route("/thank-you", name="thank_you")
     */
    public function emailSent()
    {

        return $this->render('thank_you.html.twig', [
             'meassage' => 'form submitted successfully',
         ]);
    }
    /**
     * @Route(
     *   "/api/contact/create",
     *   methods={ "PUT", "POST"},
     *   name="api_create_user"
     * )
     *
     */
    public function createContact(Request $request, MailGenerator $mailer_service)
    {
      $contact_fields = [
        'firstName',
        'lastName',
        'emailAddress',
        'message',
      ];
      $errors = [];
      $valid = true;
      foreach ($contact_fields  as $field) {
        if ($request->request->get($field) === NULL) {
          $valid = false;
          $errors[] = $field;
        }
      }

      if ($valid) {
        $contact = new Contact();
        $contact->setFirstName($request->request->get('firstName'));
        $contact->setLastName($request->request->get('lastName'));
        $contact->setEmailAddress($request->request->get('emailAddress'));
        $contact->setMessage($request->request->get('message'));

        try {
          $entityManager = $this->getDoctrine()->getManager();
          $entityManager->persist($contact);
          $entityManager->flush();

          $admin_user = $this->getAdminUser();
          $res = $mailer_service->createMail($contact,$admin_user);
        } catch (\Exception $e) {
          return new Response('Error '.$e->getMessage(), Response::HTTP_OK);
        }

        return $this->json(['Contact' => $contact]);
      }

      return $this->json(['These fields are missing' => $errors]);
    }

    public function getAdminUser()
    {
      $repository = $this->getDoctrine()->getRepository(User::class);
      $role = $this->getParameter('app.admin_role');
      $user = $repository->findByRole($role ?: 'ROLE_API');
      return $user[0];
    }
}
