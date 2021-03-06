<?php

/*
 * This file is part of the Phospr package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phospr\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Phospr\CoreBundle\Entity\Journal;
use Phospr\DoubleEntryBundle\Form\Type\JournalType;
use Phospr\DoubleEntryBundle\Exception\JournalImbalanceException;

/**
 * JournalController
 *
 * @author Tom Haskins-Vaughan <tom@tomhv.uk>
 * @since  0.8.0
 */
class JournalController extends Controller
{
    /**
     * post
     *
     * @author Tom Haskins-Vaughan <tom@tomhv.uk>
     * @since  0.8.0
     *
     * @Template
     * @ParamConverter("journal", class="Phospr\CoreBundle\Entity\Journal")
     *
     * @param  Request $request
     * @param  Journal $journal
     */
    public function postAction(Request $request, Journal $journal)
    {
        $em = $this->get('doctrine')->getManager();

        $account = $em
            ->getRepository('PhosprCoreBundle:Account')
            ->findOneBy(array('path' => $request->get('path')))
        ;

        $journal->post();

        $em->persist($journal);
        $em->flush();

        return $this->redirect($this->generateUrl(
            'phospr_account_show', array('path' => $account->getPath())
        ));
    }

    /**
     * edit
     *
     * @author Tom Haskins-Vaughan <tom@tomhv.uk>
     * @since  0.10.0
     *
     * @Template
     * @ParamConverter("journal", class="Phospr\CoreBundle\Entity\Journal")
     *
     * @param  Request $request
     * @param  Journal $journal
     */
    public function showAction(Request $request, Journal $journal)
    {
        return array(
            'journal' => $journal,
        );
    }

    /**
     * edit
     *
     * @author Tom Haskins-Vaughan <tom@tomhv.uk>
     * @since  0.8.0
     *
     * @Template
     * @ParamConverter("journal", class="Phospr\CoreBundle\Entity\Journal")
     *
     * @param  Request $request
     * @param  Journal $journal
     */
    public function editAction(Request $request, Journal $journal)
    {
        // Let's add a blank posting
        $journal->addPosting(
            $this->get('phospr.posting_handler')->createPosting()
        );

        $form = $this->createForm('journal', $journal);
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                foreach ($form->getData()->getPostings() as $posting) {
                    if (!$posting->getAccount()) {
                        $form->getData()->removePosting($posting);
                    }
                }

                $em = $this->get('doctrine')->getManager();
                $em->persist($form->getData());
                $em->flush();

                return $this->redirect($this->generateUrl(
                    'phospr_journal_show', array('id' => $journal->getId())
                ));
            } catch (JournalImbalanceException $e) {
                exit($e->getMessage());
            }
        }

        return array(
            'journal' => $journal,
            'form' => $form->createView(),
        );
    }
}
