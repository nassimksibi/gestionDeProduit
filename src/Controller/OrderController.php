<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/order')]
#[IsGranted('ROLE_ADMIN')]
final class OrderController extends AbstractController
{
    #[Route(name: 'app_admin_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ProductRepository $productRepository): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set order reference for all order items
            foreach ($order->getOrderItems() as $item) {
                $item->setOrder($order);
                // Ensure price is set from product if not already set
                if ($item->getProduct() && !$item->getPrice()) {
                    $item->setPrice($item->getProduct()->getPrice());
                }
            }
            // Recalculate total from order items
            $order->setTotal($order->calculateTotal());
            $entityManager->persist($order);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form,
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager, ProductRepository $productRepository): Response
    {
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set order reference for all order items and ensure prices are set
            foreach ($order->getOrderItems() as $item) {
                $item->setOrder($order);
                // Ensure price is set from product if not already set
                if ($item->getProduct() && !$item->getPrice()) {
                    $item->setPrice($item->getProduct()->getPrice());
                }
            }
            // Recalculate total from order items
            $order->setTotal($order->calculateTotal());
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_order_index', [], Response::HTTP_SEE_OTHER);
    }
}

