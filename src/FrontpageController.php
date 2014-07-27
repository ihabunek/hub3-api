<?php

namespace BigFish\Hub3\Api;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class FrontpageController
{
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function indexAction(Request $request)
    {
        return $this->app['twig']->render('frontpage.twig');        
    }

    public function showFormAction(Request $request)
    {
        // some default data for when the form is displayed the first time
        $data = array(
        );

        $form = $this->getForm($data);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            var_dump($data);
            // do something with the data

            // redirect somewhere
            // return $this->app->redirect('...');
        }

        return $this->app['twig']->render('form.twig', [
            'form' => $form->createView()
        ]);
    }

    private function getForm($data)
    {
        $form = $this->app['form.factory']->createBuilder('form', $data)
            ->add('amount', 'text', [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range([
                        'min' => 0.01,
                        'max' => pow(10, 13) - 0.01,
                    ])
                ],
                'label_attr' => ['class' => 'xxx'],
                'attr'       => ['class' => 'yyy'],
            ])
            ->add('payer', 'textarea', [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => 100])
                ]
            ])
            ->add('payee', 'textarea', [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => 100])
                ]
            ])
            ->getForm();

        return $form;
    }
}
