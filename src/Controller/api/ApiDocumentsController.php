<?php

namespace App\Controller\api;

use App\Entity\Documents;
use App\Form\DocumentsType;
use App\Repository\DocumentsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/apidocuments')]
class ApiDocumentsController extends AbstractController
{
    #[Route('/list', name: 'app_apidocuments_index', methods: ['GET'])]
    public function index(DocumentsRepository $documentsRepository): Response
    {
        $documents = $documentsRepository->findAll();

        $data = [];

        foreach ($documents as $p) {
            $data[] = [
                'id' => $p->getId(),
                'date' => $p->getDate(),
                'description' => $p->getDescription(),
                'docname' => $p->getDocname(),
            ];
        }

        return $this->json($data, $status = 200, $headers = ['Access-Control-Allow-Origin'=>'*']);
    }

    #[Route('/file/{id}', name: 'app_apidocuments_file', methods: ['GET'])]
    public function getFile(Documents $document): Response
    {
        $filePath = $this->getParameter('documents_directory').'/'.$document->getDocument();
        $fileContent = file_get_contents($filePath);
        $response = new Response($fileContent);
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $document->getDocname());
        $response->headers->set('Content-Disposition', $disposition);
        return $response;
    }

    #[Route('/create', name: 'app_apidocuments_create', methods: ['POST'])]
    public function create(Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger): Response
    {
        $document = new Documents();
        $form = $this->createForm(DocumentsType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();

            $fileName = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $newFileName = $fileName.'.'.$file->guessExtension();

            try {
                $file->move(
                    $this->getParameter('documents_directory'), 
                    $newFileName 
                );
            } catch (FileException $e) {
                return $this->json([
                    'message' => 'Error al cargar el archivo'
                ], $status = 400);
            }

           
            $document->setDocname($newFileName);

      


        }
}
}

