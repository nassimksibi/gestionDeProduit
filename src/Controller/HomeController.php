<?php

namespace App\Controller;

use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    #[Route('/', name: 'home')]
    public function index(StatisticsService $statisticsService): Response
    {
        return $this->render('home/index.html.twig', [
            'totalProducts' => $statisticsService->getTotalProducts(),
            'totalCustomers' => $statisticsService->getTotalCustomers(),
            'totalOrders' => $statisticsService->getTotalOrders(),
            'totalRevenue' => $statisticsService->getTotalRevenue(),
            'averageOrderValue' => $statisticsService->getAverageOrderValue(),
            'lowStockProducts' => $statisticsService->getLowStockProducts(5),
            'mostSoldProducts' => $statisticsService->getMostSoldProducts(5),
            'recentOrders' => $statisticsService->getRecentOrders(5),
            'ordersByStatus' => $statisticsService->getOrdersByStatus(),
        ]);
    }
}
