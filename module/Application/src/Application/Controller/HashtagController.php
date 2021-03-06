<?php

namespace Application\Controller;

use SlmQueueDoctrine\Queue\DoctrineQueue;
use SlmQueue\Job\JobPluginManager;
use Application\Form\HashtagForm;
use Application\Repository\HashtagRepository;
use Application\Service\HashtagService;
use Zend\Hydrator\ClassMethods;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\View\Model\ViewModel;

class HashtagController extends AbstractActionController
{
    protected $hashtagRepository;
    protected $hashtagForm;
    protected $hashtagService;
    protected $doctrineObject;

    /** @var \SlmQueueDoctrine\Queue\DoctrineQueue $queue */
    protected $queue;

    /** @var \SlmQueue\Job\JobPluginManager $jobPluginManager */
    protected $jobPluginManager;

    public function __construct(
        HashtagRepository $hashtagRepository,
        HashtagForm $hashtagForm,
        HashtagService $hashtagService
    )
    {
        $this->hashtagRepository = $hashtagRepository;
        $this->hashtagForm = $hashtagForm;
        $this->hashtagService = $hashtagService;
    }

    public function indexAction()
    {
        $data = $this->hashtagRepository->findAll();

        $page = $this->params()->fromRoute('page');

        $paginator = new Paginator(new ArrayAdapter($data));
        $paginator->setCurrentPageNumber($page);

        return new ViewModel([
            'data' => $paginator,
            'page' => $page
        ]);
    }

    public function addAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = array_merge(
                $this->getRequest()->getPost()->toArray()
            );

            $this->hashtagForm->setData($data);

            if ($this->hashtagForm->isValid())
            {
                $result = $this->hashtagService->create($data);

                if ($result instanceof \Application\Entity\Hashtag) {

                    $searchHashtagInstagramJob = $this->getJobPluginManager()->get('Application\Job\SearchHastagInstagramJob');
                    $searchHashtagInstagramJob->setContent([
                        'id' => $result->getId(),
                        'campaign_id' => $result->getCampaign()->getId(),
                    ]);
                    $this->getQueue()->push($searchHashtagInstagramJob,[
                        'delay' => $result->getRunTimeScript() . ' minutes'
                    ]);

                    $this->flashMessenger()->addMessage([
                        'type' => 'success',
                        'title' => 'Yeah!',
                        'message' => 'Ação realizada c/ sucesso!'
                    ]);

                    return $this->redirect()->toRoute('hashtag');
                }

                $this->flashMessenger()->addMessage([
                    'type' => 'error',
                    'title' => 'Ops!',
                    'message' => 'Falha ao realizar ação.'
                ]);

                return $this->redirect()->toRoute('hashtag');
            }
        }

        $viewModel = new ViewModel([
            'form' => $this->hashtagForm
        ]);
        $viewModel->setTemplate('application/hashtag/form.phtml');

        return $viewModel;
    }

    public function editAction()
    {
        $id = $this->params()->fromRoute('id');

        $hashtag = $this->hashtagRepository->findOneById($id);
        $data = (new ClassMethods())->extract($hashtag);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = array_merge(
                $this->getRequest()->getPost()->toArray()
            );

            $this->hashtagForm->setData($data);

            if ($this->hashtagForm->isValid())
            {
                $result = $this->hashtagService->update($id, $data);

                if ($result instanceof \Application\Entity\Hashtag) {
                    $this->flashMessenger()->addMessage([
                        'type' => 'success',
                        'title' => 'Yeah!',
                        'message' => 'Ação realizada c/ sucesso!'
                    ]);

                    return $this->redirect()->toRoute('hashtag');
                }

                $this->flashMessenger()->addMessage([
                    'type' => 'error',
                    'title' => 'Ops!',
                    'message' => 'Falha ao realizar ação.'
                ]);

                return $this->redirect()->toRoute('hashtag');
            }
        }

        $viewModel = new ViewModel([
            'form' => $this->hashtagForm->setData($data)
        ]);
        $viewModel->setTemplate('application/hashtag/form.phtml');

        return $viewModel;
    }

    public function deleteAction()
    {
        $id = $this->params()->fromRoute('id');

        $result = $this->hashtagService->delete($id);

        if ($result === true) {
            $this->flashMessenger()->addMessage([
                'type' => 'success',
                'title' => 'Yeah!',
                'message' => 'Ação realizada c/ sucesso!'
            ]);

            return $this->redirect()->toRoute('hashtag');
        }

        $this->flashMessenger()->addMessage([
            'type' => 'error',
            'title' => 'Ops!',
            'message' => 'Falha ao realizar ação.'
        ]);

        return $this->redirect()->toRoute('hashtag');
    }

    /**
     * @return DoctrineQueue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * @param DoctrineQueue $queue
     * @return HashtagController
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * @return JobPluginManager
     */
    public function getJobPluginManager()
    {
        return $this->jobPluginManager;
    }

    /**
     * @param JobPluginManager $jobPluginManager
     * @return HashtagController
     */
    public function setJobPluginManager($jobPluginManager)
    {
        $this->jobPluginManager = $jobPluginManager;
        return $this;
    }
}