<?php

namespace App\Controllers\Api;

use App\Models\EventParticipationModel;
use App\Services\AuthService;
use CodeIgniter\RESTful\ResourceController;

class EventParticipationController extends ResourceController
{
    protected EventParticipationModel $eventModel;
    protected $userId;

    public function __construct()
    {
        $this->eventModel = new EventParticipationModel();
        $this->userId = AuthService::getUserId();
    }

    public function index()
    {
        $events = $this->eventModel
            ->forUser($this->userId)
            ->orderBy('event_date', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => true,
            'data' => $events,
        ]);
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];

        $data['user_id'] = $this->userId;

        if (!$this->eventModel->insert($data)) {
            return $this->failValidationErrors($this->eventModel->errors(), 400);
        }

        return $this->respondCreated([
            'status' => true,
            'message' => 'Event participation recorded successfully.',
            'data' => $this->eventModel->find($this->eventModel->getInsertID()),
        ]);
    }

    public function show($id = null)
    {
        $event = $this->eventModel->findForUser($id, $this->userId);
        
        if (!$event) {
            return $this->failNotFound('Event participation not found.');
        }

        return $this->respond([
            'status' => true,
            'data' => $event,
        ]);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true) ?? [];

        $event = $this->eventModel->findForUser($id, $this->userId);
        
        if (!$event) {
            return $this->failNotFound('Event participation not found.');
        }

        if (!$this->eventModel->update($id, $data)) {
            return $this->failValidationErrors($this->eventModel->errors(), 400);
        }

        return $this->respond([
            'status' => true,
            'message' => 'Event participation updated successfully.',
            'data' => $this->eventModel->find($id),
        ]);
    }

    public function delete($id = null)
    {
        $event = $this->eventModel->findForUser($id, $this->userId);
        
        if (!$event) {
            return $this->failNotFound('Event participation not found.');
        }

        if (!$this->eventModel->delete($id)) {
            return $this->failServerError('Could not delete event participation.');
        }

        return $this->respondDeleted([
            'status' => true,
            'message' => 'Event participation deleted successfully.',
        ]);
    }
}
