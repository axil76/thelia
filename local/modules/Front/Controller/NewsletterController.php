<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace Front\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\Event\Newsletter\NewsletterEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Form\Definition\FrontForm;
use Thelia\Log\Tlog;
use Thelia\Model\Customer;

/**
 * Class NewsletterController
 * @package Thelia\Controller\Front
 * @author Manuel Raynaud <manu@thelia.net>
 */
class NewsletterController extends BaseFrontController
{

    public function subscribeAction()
    {
        $errorMessage = false;
        $newsletterForm = $this->createForm(FrontForm::NEWSLETTER);

        try {
            $form = $this->validateForm($newsletterForm);

            $event = new NewsletterEvent(
                $form->get('email')->getData(),
                $this->getRequest()->getSession()->getLang()->getLocale()
            );

            /** @var Customer $customer */
            if (null !== $customer = $this->getSecurityContext()->getCustomerUser()) {
                $event->setFirstname($customer->getFirstname());
                $event->setLastname($customer->getLastname());
            } else {
                $event->setFirstname($form->get('firstname')->getData());
                $event->setLastname($form->get('lastname')->getData());
            }

            $this->dispatch(TheliaEvents::NEWSLETTER_SUBSCRIBE, $event);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            Tlog::getInstance()->error(sprintf('Error during newsletter subscription : %s', $errorMessage));
        }


        // If Ajax Request
        if ($this->getRequest()->isXmlHttpRequest()) {
            $response = new JsonResponse();

            if ($errorMessage) {
                $response = $response->setContent(array(
                    "success" => false,
                    "message" => $errorMessage
                ));
            } else {
                $response = $response->setContent(array(
                    "success" => true,
                    "message" => $this->getTranslator()->trans(
                        "Thanks for signing up! We'll keep you posted whenever we have any new updates."
                    )
                ));
            }

            return $response;

        } else {
            if ($errorMessage) {
                $newsletterForm->setErrorMessage($errorMessage);
                $this->getParserContext()->setGeneralError($errorMessage);
            }

            $this->getParserContext()->addForm($newsletterForm);
        }
    }
}
