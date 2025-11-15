<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my-orders')]
#[IsGranted('ROLE_CUSTOMER')]
class CustomerOrderController extends AbstractController
{
    #[Route('', name: 'app_customer_orders')]
    public function index(OrderRepository $orderRepository): Response
    {
        /** @var Customer $customer */
        $customer = $this->getUser();

        $orders = $orderRepository->findBy(
            ['customer' => $customer],
            ['orderDate' => 'DESC']
        );

        return $this->render('customer_order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/{id}', name: 'app_customer_order_show')]
    public function show(int $id, OrderRepository $orderRepository): Response
    {
        /** @var Customer $customer */
        $customer = $this->getUser();

        $order = $orderRepository->find($id);

        if (!$order || $order->getCustomer() !== $customer) {
            throw $this->createAccessDeniedException('You cannot access this order.');
        }

        return $this->render('customer_order/show.html.twig', [
            'order' => $order,
        ]);
    }
}

