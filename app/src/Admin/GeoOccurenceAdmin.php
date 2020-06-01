<?php
    
    declare(strict_types=1);
    
    namespace App\Admin;
    
    use CrEOF\Geo\WKT\Parser;
    use CrEOF\Spatial\PHP\Types\Geometry\Point;
    use CrEOF\Spatial\PHP\Types\Geometry\GeometryInterface;
    use Sonata\AdminBundle\Admin\AbstractAdmin;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\AdminBundle\Datagrid\ListMapper;
    use Sonata\AdminBundle\Form\FormMapper;
    use Sonata\AdminBundle\Show\ShowMapper;
    use Sonata\CoreBundle\Form\Type\DatePickerType;
    use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
    use Symfony\Component\Form\Extension\Core\Type\TextType;
    use Vich\UploaderBundle\Form\Type\VichImageType;
    use App\Application\Sonata\UserBundle\Entity\User;
    use Doctrine\ORM\EntityRepository;
    use App\Repository\UserRepository;
    use Symfony\Bridge\Doctrine\Form\Type\EntityType;
    
    final class GeoOccurenceAdmin extends AbstractAdmin
    {
        
        public function prePersist($object)
        {
            $parser = new Parser('Point(' . $object->getLocation() . ')');
            $geo = $parser->parse();
            $g = new \CrEOF\Spatial\PHP\Types\Geometry\Point($geo['value'], '4326');
            $object->setLocation($g);
            //dump ('prepersist',$geo,$g);die;
            
        }
        
        public function preUpdate($object)
        {
            $parser = new Parser('Point(' . $object->getLocation() . ')');
            $geo = $parser->parse();
            $g = new \CrEOF\Spatial\PHP\Types\Geometry\Point($geo['value'], '4326');
            $object->setLocation($g);
            //dump ('preupdate',$geo,$g);die;
        }
        
        
        protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
        {
            $datagridMapper
                //->add('id')
                
                ->add('mamias', null, ['label' => 'Species Name', 'show_filter' => true])
                ->add('date_occurence', null, ['label' => 'Date of the Occurence', 'format' => 'd/M/y'])
                ->add('createdAt', null, ['label' => 'Created At', 'format' => 'dd/MM/y'])
                //->add('updatedAt', null, array('label' => 'Updated At', 'format' => 'dd/MM/y'))
                //->add('validator', null, ['label' => 'Validated by'])
                ->add(
                    'status',
                    null,
                    ['label' => 'Status', 'show_filter' => true],
                    ChoiceType::class,
                    [
                        'choices' => [
                            'Validated' => 'Validated',
                            'Submitted' => 'Submitted',
                            'Rejected' => 'Rejected'
                            //'Information requested' => 'Information requested'
                        ],
                        'label' => 'Status',
                    ]
                )
                ->add('user', null, ['label' => 'declared By', 'show_filter' => true])
                ->add('country', null, ['label' => 'Country', 'show_filter' => true]);
        }
        
        protected function configureListFields(ListMapper $listMapper): void
        {
            $listMapper
                ->add('id')
                ->add('mamias', null, ['label' => 'Species Name', 'header_style' => 'width: 10%'])
                ->add('country', null, ['label' => 'Country'])
                ->add('imageFile', null, ['label' => 'Picture', 'template' => 'declaration/picture.html.twig'])
                ->add('Location', null, ['label' => 'Coordinates'])
                //->add('longitude', null, array('label' => 'longitude'))
                ->add('date_occurence', null, ['label' => 'Date of the Occurence', 'date_format' => 'yyyy'])
                //->add('note_occurence', null, array('label' => 'Note'))
                ->add(
                    'status',
                    ChoiceType::class,
                    [
                        'choices' => [
                            'choices' => [
                                'Validated' => 'Validated',
                                'Rejected' => 'Rejected',
                                'Submitted' => 'Submitted'
                            ],
                            'editable' => true
                        ],
                        ['label' => 'Status'],
                    ]
                )
                ->add('createdAt', null, ['label' => 'Created At', 'format' => 'd/M/y'])
                //->add('updatedAt', null, array('label' => 'Updated At','format' => 'd/M/y'))
                ->add('user', null, ['label' => 'declared By'])
                ->add('validator', null, ['label' => 'Validated by', 'disabled' => true])
                ->add(
                    '_action',
                    null,
                    [
                        'actions' => [
                            'show' => [],
                            'edit' => [],
                            'delete' => [],
                        ],
                    ]
                );
        }
        
        protected function configureFormFields(FormMapper $formMapper): void
        {
            $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();
            $formtest1 = $this->id($this->getSubject());
            //$formtest2 = $this->getSubject ()->getId ();
            //dump($formtest1);die;
            
            if (null === $formtest1) {
                if ($this->hasParentFieldDescription()) {
                    $formMapper
                        ->add('country', null, ['label' => 'Country1', 'disabled' => false])
                        ->add('Location', null, ['label' => 'Coordinates', 'disabled' => false, 'required' => true])
                        ->add(
                            'date_occurence',
                            DatePickerType::class,
                            ['label' => 'Date of the Occurence', 'disabled' => false],
                            [
                                'format' => 'yyyy',
                                'dp_use_current' => true,
                                'dp_show_today' => true,
                                'dp_collapse' => true,
                                'dp_view_mode' => 'decades',
                                'dp_min_view_mode' => 'years',
                            ]
                        )
                        //->add('note_occurence', null, array('label' => 'Note'))
                        // ->add ('createdAt', DatePickerType::class,['label' => 'Created At', 'disabled' => false],
                        //     ['format' => 'd/M/yy','dp_side_by_side' => true,'dp_use_current' => true,'dp_collapse' => true,
                        //         'dp_calendar_weeks' => false,'dp_view_mode' => 'days','dp_min_view_mode' => 'days',])
                        //->add('updatedAt', DatePickerType::class, array('label' => 'Updated At'), ['format' => 'dd/MM/yyyy',
                        //    'dp_side_by_side' => true,'dp_use_current' => true,'dp_collapse' => true,'dp_view_mode' => 'days',
                        //    'dp_min_view_mode' => 'year',])
                        
                        ->add(
                            'imageFile',
                            VichImageType::class,
                            [
                                'label' => 'Picture',
                                'required' => false,
                                'download_link' => true,
                                'allow_delete' => true,
                                'download_uri' => true,
                                'image_uri' => true,
                                'disabled' => true,
                            ]
                        )
                        ->add('user', null, ['label' => 'declared By', 'disabled' => false, 'data' => $user])
                        ->add(
                            'status',
                            ChoiceType::class,
                            [
                                'choices' => [
                                    'Validated' => 'Validated',
                                    'Submitted' => 'Submitted',
                                    'Rejected' => 'Rejected'
                                    //'Information requested' => 'Information requested'
                                ],
                                'label' => 'Status',
                            ]
                        )
                        ->add('validator', EntityType::class, ['label' => 'Validated by', 'disabled' => false, 'required' => false,
                            'class' => User::class,
                            'expanded' => false,
                            'multiple' => true,
                            'query_builder' => function (EntityRepository $er) {
                                return $er->createQueryBuilder('d')
                                    ->where('d.roles  LIKE :ROLE')
                                    ->setParameter('ROLE', '%"' . 'ROLE_S' . '"%');
                            }])
                        ->add('notes', null, ['label' => 'Comments by the Admin']);
                } else {
                    $formMapper
                        ->add('mamias', null, ['label' => 'Species Name', 'disabled' => false])
                        ->add('country', null, ['label' => 'Country', 'disabled' => false])
                        ->add('Location', null, ['label' => 'Coordinates', 'disabled' => false])
                        ->add('validator', null, ['label' => 'Validated by', 'disabled' => false])
                        //->add(' Location', PointType::class, array('label' => 'Coordinates'))
                        //->add('longitude', null, array('label' => 'longitude'))
                        ->add(
                            'date_occurence',
                            DatePickerType::class,
                            ['label' => 'Date of the Occurence', 'disabled' => false],
                            [
                                'format' => 'yyyy',
                                'dp_use_current' => true,
                                'dp_show_today' => true,
                                'dp_collapse' => true,
                                'dp_view_mode' => 'years',
                                'dp_min_view_mode' => 'years',
                            ]
                        )
                        //->add('note_occurence', null, array('label' => 'Note'))
                        ->add(
                            'createdAt',
                            DatePickerType::class,
                            ['label' => 'Created At', 'disabled' => true],
                            [
                                'format' => 'd/M/yy',
                                'dp_side_by_side' => true,
                                'dp_use_current' => true,
                                'dp_collapse' => true,
                                'dp_calendar_weeks' => false,
                                'dp_view_mode' => 'days',
                                'dp_min_view_mode' => 'days',
                            ]
                        )
                        //->add('updatedAt', DatePickerType::class, array('label' => 'Updated At'), ['format' => 'dd/MM/yyyy',
                        //    'dp_side_by_side' => true,'dp_use_current' => true,'dp_collapse' => true,'dp_view_mode' => 'days',
                        //    'dp_min_view_mode' => 'year',])
                        ->add(
                            'imageFile',
                            VichImageType::class,
                            [
                                'label' => 'Picture',
                                'required' => false,
                                'download_link' => true,
                                'allow_delete' => true,
                                'download_uri' => true,
                                'image_uri' => true,
                                'disabled' => true,
                            ]
                        )
                        ->add('user', null, ['label' => 'declared By', 'disabled' => true, 'data' => $user])
                        ->add(
                            'status',
                            ChoiceType::class,
                            [
                                'choices' => [
                                    'Validated' => 'Validated',
                                    'Submitted' => 'Submitted',
                                    'Rejected' => 'Rejected'
                                    //'Information requested' => 'Information requested'
                                ],
                                'label' => 'Status',
                            ]
                        )
                        //->add ('validator', null, ['label' => 'Validated by', 'disabled' => false])
                        ->add('validator', EntityType::class, ['label' => 'Validated by', 'disabled' => false, 'required' => false,
                            'class' => User::class,
                            'expanded' => false,
                            'multiple' => true,
                            'query_builder' => function (EntityRepository $er) {
                                return $er->createQueryBuilder('d')
                                    ->where('d.roles  LIKE :ROLE')
                                    ->setParameter('ROLE', '%"' . 'ROLE_S' . '"%');
                            }])
                        ->add('notes', null, ['label' => 'Comments by the Admin']);
                }
            } else {
                $formMapper
                    ->add('country', null, ['label' => 'Country3', 'disabled' => true])
                    ->add('Location', null, ['label' => 'Coordinates', 'disabled' => true])
                    ->add(
                        'date_occurence',
                        DatePickerType::class,
                        ['label' => 'Date of the Occurence', 'disabled' => true],
                        [
                            'format' => 'yyyy',
                            'dp_use_current' => true,
                            'dp_show_today' => true,
                            'dp_collapse' => true,
                            'dp_view_mode' => 'years',
                            'dp_min_view_mode' => 'years',
                        ]
                    )
                    //->add('note_occurence', null, array('label' => 'Note'))
                    // ->add ('createdAt', DatePickerType::class,['label' => 'Created At', 'disabled' => false],
                    //     ['format' => 'd/M/yy','dp_side_by_side' => true,'dp_use_current' => true,'dp_collapse' => true,
                    //         'dp_calendar_weeks' => false,'dp_view_mode' => 'days','dp_min_view_mode' => 'days',])
                    //->add('updatedAt', DatePickerType::class, array('label' => 'Updated At'), ['format' => 'dd/MM/yyyy',
                    //    'dp_side_by_side' => true,'dp_use_current' => true,'dp_collapse' => true,'dp_view_mode' => 'days',
                    //    'dp_min_view_mode' => 'year',])
                    
                    ->add(
                        'imageFile',
                        VichImageType::class,
                        [
                            'label' => 'Picture',
                            'required' => false,
                            'download_link' => true,
                            'allow_delete' => true,
                            'download_uri' => true,
                            'image_uri' => true,
                            'disabled' => true,
                        ]
                    )
                    ->add('user', null, ['label' => 'declared By', 'disabled' => true, 'data' => $user])
                    ->add(
                        'status',
                        ChoiceType::class,
                        [
                            'choices' => [
                                'Validated' => 'Validated',
                                'Submitted' => 'Submitted',
                                'Rejected' => 'Rejected'
                                //'Information requested' => 'Information requested'
                            ],
                            'label' => 'Status',
                        ]
                    )
                    ->add('validator', EntityType::class, ['label' => 'Validated by', 'disabled' => false, 'required' => false,
                        'class' => User::class,
                        'expanded' => false,
                        'multiple' => true,
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('d')
                                ->where('d.roles  LIKE :ROLE')
                                ->setParameter('ROLE', '%"' . 'ROLE_S' . '"%');
                        }])
                    ->add('notes', null, ['label' => 'Comments by the Admin']);
            }
        }
        
        
        protected function configureShowFields(ShowMapper $showMapper): void
        {
            $showMapper
                ->add('id')
                ->add('mamias', null, ['label' => 'Species Name'])
                //->add('imageFile', 'vich_image')
                ->add('country', null, ['label' => 'Country'])
                ->add('Location', PointType::class, ['label' => 'Location'])
                //->add('longitude', null, array('label' => 'longitude'))
                ->add('date_occurence', null, ['label' => 'Date of the Occurence'])
                ->add('imageFile', null, ['label' => 'Picture', 'template' => 'declaration/picture.html.twig'])
                ->add('note_occurence', null, ['label' => 'Note'])
                ->add('createdAt', null, ['label' => 'Created At'])
                ->add('updatedAt', null, ['label' => 'Updated At'])
                ->add('status', null, ['label' => 'Status'])
                ->add('user', null, ['label' => 'declared By']);
        }
    }
