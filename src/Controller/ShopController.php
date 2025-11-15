<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/shop')]
class ShopController extends AbstractController
{
    #[Route('', name: 'app_shop_index')]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('shop/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/add-to-cart/{id}', name: 'app_shop_add_to_cart', methods: ['POST'])]
    #[IsGranted('ROLE_CUSTOMER')]
    public function addToCart(Product $product, Request $request, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        $quantity = (int) $request->request->get('quantity', 1);

        if (isset($cart[$product->getId()])) {
            $cart[$product->getId()] += $quantity;
        } else {
            $cart[$product->getId()] = $quantity;
        }

        $session->set('cart', $cart);

        $this->addFlash('success', sprintf('Added %d x %s to cart', $quantity, $product->getLabel()));

        return $this->redirectToRoute('app_shop_index');
    }

    #[Route('/cart', name: 'app_shop_cart')]
    #[IsGranted('ROLE_CUSTOMER')]
    public function cart(ProductRepository $productRepository, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        $cartItems = [];
        $total = 0;

        foreach ($cart as $productId => $quantity) {
            $product = $productRepository->find($productId);
            if ($product) {
                $subtotal = $product->getPrice() * $quantity;
                $total += $subtotal;
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];
            }
        }

        return $this->render('shop/cart.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }

    #[Route('/cart/update', name: 'app_shop_update_cart', methods: ['POST'])]
    #[IsGranted('ROLE_CUSTOMER')]
    public function updateCart(Request $request, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        $quantities = $request->request->all('quantities');

        foreach ($quantities as $productId => $quantity) {
            $quantity = (int) $quantity;
            if ($quantity > 0) {
                $cart[$productId] = $quantity;
            } else {
                unset($cart[$productId]);
            }
        }

        $session->set('cart', $cart);

        return $this->redirectToRoute('app_shop_cart');
    }

    #[Route('/cart/remove/{id}', name: 'app_shop_remove_from_cart', methods: ['POST'])]
    #[IsGranted('ROLE_CUSTOMER')]
    public function removeFromCart(Product $product, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        unset($cart[$product->getId()]);
        $session->set('cart', $cart);

        $this->addFlash('success', 'Product removed from cart');

        return $this->redirectToRoute('app_shop_cart');
    }

    #[Route('/checkout', name: 'app_shop_checkout', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CUSTOMER')]
    public function checkout(
        Request $request,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        SessionInterface $session
    ): Response {
        /** @var Customer $customer */
        $customer = $this->getUser();

        $cart = $session->get('cart', []);
        if (empty($cart)) {
            $this->addFlash('warning', 'Your cart is empty');
            return $this->redirectToRoute('app_shop_cart');
        }

        if ($request->isMethod('POST')) {
            $order = new Order();
            $order->setCustomer($customer);
            $order->setStatus('pending');
            $order->setOrderDate(new \DateTime());

            $total = 0;
            foreach ($cart as $productId => $quantity) {
                $product = $productRepository->find($productId);
                if ($product && $product->getStock() >= $quantity) {
                    $orderItem = new OrderItem();
                    $orderItem->setProduct($product);
                    $orderItem->setQuantity($quantity);
                    $orderItem->setPrice($product->getPrice());
                    $orderItem->setOrder($order);

                    $order->addOrderItem($orderItem);
                    $total += $orderItem->getSubtotal();

                    // Update stock
                    $product->setStock($product->getStock() - $quantity);
                }
            }

            $order->setTotal($total);
            $entityManager->persist($order);
            $entityManager->flush();

            // Clear cart
            $session->remove('cart');

            $this->addFlash('success', 'Order placed successfully!');

            return $this->redirectToRoute('app_customer_orders');
        }

        // GET request - show checkout page
        $cartItems = [];
        $total = 0;

        foreach ($cart as $productId => $quantity) {
            $product = $productRepository->find($productId);
            if ($product) {
                $subtotal = $product->getPrice() * $quantity;
                $total += $subtotal;
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];
            }
        }

        return $this->render('shop/checkout.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
            'customer' => $customer,
        ]);
    }
}

