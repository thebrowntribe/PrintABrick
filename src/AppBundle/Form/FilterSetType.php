<?php

namespace AppBundle\Form;

use AppBundle\Client\Brickset\Entity\Subtheme;
use AppBundle\Client\Brickset\Entity\Theme;
use AppBundle\Client\Brickset\Entity\Year;
use AppBundle\Service\CollectionService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FilterSetType extends AbstractType
{
    private $collectionService;

    public function __construct(CollectionService $collectionService)
    {
        $this->collectionService = $collectionService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add("theme", ChoiceType::class, [
                'choices' => $this->collectionService->getThemes(),
                'choice_label' => function(Theme $theme = null) {
                    return $theme ? $theme->getTheme().' ('.$theme->getSetCount().')' : '';
                },
                'placeholder' => '',
                'required' => false

            ]);

        $formModifier = function (Form $form, Theme $theme = null) {
            $subthemes = null === $theme ? [] : $this->collectionService->getSubthemesByTheme($theme);
            $years = null === $theme ? [] : $this->collectionService->getYearsByTheme($theme);

            $form->add("subtheme", ChoiceType::class, [
                'choices' => $subthemes,
                'choice_label' => function(Subtheme $subtheme = null) {
                    return $subtheme ? $subtheme->getSubtheme() : '';
                },
                'placeholder' => '',
                'required' => false
            ]);

            $form->add("years", ChoiceType::class, [
                'choices' => $years,
                'choice_label' => function(Year $year = null) {
                    return $year ? $year->getYear() : '';
                },
                'placeholder' => '',
                'required' => false
            ]);
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $theme = $data === null ? null : $this->collectionService->getThemes()[$data['theme']];
                $formModifier($event->getForm(), $theme);
            }
        );

        $builder->get('theme')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $theme = $event->getForm()->getData();
                $formModifier($event->getForm()->getParent(), $theme);
            }
        );

        $builder->add('submit',SubmitType::class);
    }
}