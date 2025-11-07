<?php

namespace App\Form;

use App\Entity\OrderItem;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class OrderItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => function(Product $product) {
                    return $product->getLabel() . ' (' . number_format($product->getPrice(), 2, '.', ',') . ' €)';
                },
                'placeholder' => 'Select a product',
                'attr' => ['class' => 'form-select product-select'],
            ])
            ->add('quantity', IntegerType::class, [
                'attr' => ['class' => 'form-control', 'min' => 1, 'value' => 1],
            ])
            ->add('price', NumberType::class, [
                'scale' => 2,
                'attr' => ['class' => 'form-control item-price', 'step' => '0.01', 'readonly' => true],
            ])
        ;

        // Auto-populate price when product is selected
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $orderItem = $event->getData();
            if ($orderItem && $orderItem->getProduct()) {
                $orderItem->setPrice($orderItem->getProduct()->getPrice());
            }
        });

        $builder->get('product')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $product = $form->getData();
            if ($product) {
                $parentForm = $form->getParent();
                $parentForm->get('price')->setData($product->getPrice());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderItem::class,
        ]);
    }
}

