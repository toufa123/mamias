<?php
    
    namespace App\Controller;
    
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\Routing\Annotation\Route;
    use App\Entity\Catalogue;
    use App\Entity\Mamias;
    use Ob\HighchartsBundle\Highcharts\Highchart;
    use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
    use Symfony\Component\HttpFoundation\Request;
    
    class MedlevelController extends AbstractController
    {
        /**
         * @Route("services/dash/med", name="med")
         */
        public function index(Request $request)
        {
            $em = $this->getDoctrine()->getManager();
            
            //Number of Species in the Catalogue
            $Species_catalogues = $em->getRepository(Catalogue::class)->getNb();
            //dump($Species_catalogues);die;
            //Number of Established Species
            $Species_established = $em->getRepository(Mamias::class)->getNbEstablished();
            
            //Number of Invasive Species
            $Species_invasive = $em->getRepository(Mamias::class)->getNbInvasive();
            
            //Number NIS/Country
            $country = $em->getRepository(Mamias::class)->getSpeciesPerCountry();
            //dump($country); die;
            $categoriesc = [];
            $datac = [];
            foreach ($country as $values) {
                $categoriesc[] = [$values['country']];
                
                $a4 = [$values['country'], $values['z']];
                array_push($datac, $a4);
            }
            //dump($categories); die;
            $ob7 = new Highchart();
            $ob7->chart->type('bar');
            $ob7->lang->noData("No Data to display");
            $ob7->chart->renderTo('barchart2');
            $ob7->title->text('NIS per Country');
            $ob7->xAxis->categories($categoriesc);
            $ob7->yAxis->title(['text' => 'Numbre of NIS']);
            $ob7->title->style(
                ['fontFamily' => 'Roboto light', 'fontSize' => '18px', 'color' => '#00AEEF', 'fontWeight' => 'bold']
            );
            $ob7->credits->text('www.mamias.org');
            $ob7->credits->href('http://www.mamias.org _target="blank"');
            //$ob5->legend->enabled(true);
            $ob7->labels->enabled(true);
            
            //$data = array($status,$results2);
            $ob7->series ([['type' => 'column', 'name' => 'Number of NIS', 'color' => '#00AEEF', 'data' => $datac]]);
            
            //draw cumulative numbre of NIS
            $cumulative = $em->getRepository(Mamias::class)->getcumulative();
            //dump($cumulative);die;
            $datacu = [];
            
            foreach ($cumulative as $values) {
                $cat[] = [$values['first_med_sighting']];
                $a = [$values['first_med_sighting'], $values['cumulative']];
                
                array_push($datacu, $a);
            }
            //dump($cat);die;
            $ob8 = new Highchart();
            $ob8->chart->type('areaspline');
            $ob8->chart->regression(true);
            $ob8->lang->noData("No Data to display");
            $ob8->chart->renderTo('linechart');
            $ob8->xAxis->categories($cat);
            $ob8->yAxis->allowDecimals(false);
            $ob8->title->text('Temporal variability in numbers of new reported marine non-indigenous species');
            $ob8->title->style(
                [
                    'fontFamily' => 'Roboto Light',
                    'fontSize' => '18px',
                    'color' => '#00AEEF',
                    'fontWeight' => 'bold'
                ]
            );
            
            $ob8->credits->text('www.mamias.org');
            $ob8->credits->href('http://www.mamias.org _target="blank"');
            $ob8->yAxis->tickInterval(5);
            $ob8->xAxis->title(['text' => 'Years']);
            $ob8->yAxis->title(['text' => 'Numbre of NIS']);
            $ob8->plotOptions->line(
                [
                    'allowPointSelect' => true,
                    'cursor' => 'pointer',
                    //'dataLabels' => ['enabled' => true],
                    'showInLegend' => true,
                    //'pointStart' => 1792
                ]
            );

            $ob8->series ([['name' => 'Cumulative Number of NIS', 'color' => '#00AEEF', 'data' => $datacu]]);
            //Ecofunctional Groups of NIS
            $groups = $em->getRepository(Mamias::class)->getSpeciesbyGroup();
            //dump($groups); die;
            $data = [];
            foreach ($groups as $values) {
                $a = [$values['ecofunctional'], $values['value']];
                array_push($data, $a);
            }
            $ob3 = new Highchart();
            $ob3->chart->renderTo('piechart1');
            $ob3->chart->style(['fontFamily' => 'roboto']);
            $ob3->title->text('Ecofunctional Groups of NIS');
            $ob3->title->style(
                ['fontFamily' => 'Roboto light', 'fontSize' => '18px', 'color' => '#00AEEF', 'fontWeight' => 'bold']
            );
            
            $ob3->credits->text('www.mamias.org');
            $ob3->credits->href('http://www.mamias.org _target="blank"');
            $ob3->lang->noData("No Data to display");
            $ob3->plotOptions->pie(
                [
                    'height' => '500',
                    'allowPointSelect' => true,
                    'cursor' => 'pointer',
                    'dataLabels' => [
                        'enabled' => true,
                        'connectorShape' => 'crookedLine',
                        'crookDistance' => '50%',
                        'format' => '<b>{point.name}</b>: {point.percentage:.1f} %'
                    ],
                    'showInLegend' => false,
                ]
            );
            //$data = array($status,$results2);
            $ob3->series([['type' => 'pie', 'name' => 'Number of NIS', 'data' => $data]]);
            
            //Establishment of the NIS
            $status = $em->getRepository(Mamias::class)->getSpeciesbyStatus();
            //dump($status); die;
            $data2 = [];
            foreach ($status as $values) {
                $a = [$values['success'], $values['value']];
                array_push($data2, $a);
            }
            $ob4 = new Highchart();
            $ob4->lang->noData("No Data to display");
            $ob4->chart->renderTo('piechart2');
            $ob4->title->text('Status & Establishment of the NIS');
            $ob4->title->style(
                ['fontFamily' => 'Roboto Light', 'fontSize' => '18px', 'color' => '#00AEEF', 'fontWeight' => 'bold']
            );
            $ob4->credits->text('www.mamias.org');
            $ob4->credits->href('http://www.mamias.org _target="blank"');
            $ob4->plotOptions->pie(
                [
                    'allowPointSelect' => true,
                    'cursor' => 'pointer',
                    'dataLabels' => [
                        'enabled' => true,
                        'connectorShape' => 'crookedLine',
                        'crookDistance' => '30%',
                        'format' => '<b>{point.name}</b>: {point.percentage:.1f} %'
                    ],
                    'showInLegend' => false,
                ]
            );
            //$data = array($status,$results2);
            $ob4->series([['type' => 'pie', 'name' => 'Number of NIS', 'data' => $data2]]);
            
            //NIS by Ecap subregions
            $em = $this->getDoctrine()->getManager();
            $ecap = $em->getRepository(Mamias::class)->getSpeciesbyEcap();
            //
            //dump($ecap); die;
            $data4 = [];
            foreach ($ecap as $values) {
                $a = [$values['ecap'], $values['value']];
                array_push($data4, $a);
            }
            $ob6 = new Highchart();
            $ob6->chart->renderTo('piechart3');
            $ob6->chart->type('pie')
                ->options3d(['enabled' => true, 'alpha' => '50', 'beta' => '0', 'depth' => '20', 'viewDistance' => '25']);
            $ob6->lang->noData("No Data to display");
            $ob6->title->text('Number of NIS / EcAp Sub-region');
            $ob6->title->style(
                ['fontFamily' => 'Roboto light', 'fontSize' => '18px', 'color' => '#00AEEF', 'fontWeight' => 'bold']
            );
            
            $ob6->credits->text('www.mamias.org');
            $ob6->credits->href('http://www.mamias.org _target="blank"');
            $ob6->plotOptions->pie(
                [
                    'allowPointSelect' => true,
                    'cursor' => 'pointer',
                    'depth' => '40',
                    'dataLabels' => [
                        'enabled' => true,
                        'connectorShape' => 'crookedLine',
                        'crookDistance' => '70%',
                        'format' => '<b>{point.name}</b>: {point.percentage:.1f} %'
                    ],
                    'showInLegend' => false,
                ]
            );
            //$data = array($status,$results2);
            $ob6->series([['type' => 'pie', 'name' => 'Number of NIS', 'data' => $data4]]);
            
            //Origin of the NIS
            $origin = $em->getRepository(Mamias::class)->getSpeciesbyOrigins();
            //dump($origin); die;
            $categories = null;
            $data3 = [];
            foreach ($origin as $values) {
                $categories[] = [$values['origin']];
                
                $a3 = [$values['origin'], $values['value']];
                array_push($data3, $a3);
            }
            //dump($categories); die;
            
            $ob5 = new Highchart();
            $ob5->lang->noData("No Data to display");
            $ob5->chart->renderTo('barchart1');
            $ob5->chart->options3d(['enabled' => true, 'alpha' => '5', 'beta' => '30', 'depth' => '100', 'viewDistance' => '25']);
            $ob5->title->text('Origin of the NIS');

            $ob5->yAxis->title(['text' => 'Numbre of NIS']);
            $ob5->xAxis->categories($categories);
            $ob5->title->style(
                ['fontFamily' => 'Roboto light', 'fontSize' => '18px', 'color' => '#00AEEF', 'fontWeight' => 'bold']
            );
            $ob5->credits->text('www.mamias.org');
            $ob5->credits->href('http://www.mamias.org _target="blank"');
            //$ob5->legend->enabled(true);
            $ob5->yAxis->allowDecimals(false);
            $ob5->labels->enabled(true);
            
            //$data = array($status,$results2);
            $ob5->series ([['type' => 'column', 'name' => 'Number of NIS', 'color' => '#00AEEF', 'data' => $data3]]);
            
            //numbre of NIS by Pathway
            $vector = $em->getRepository(Mamias::class)->getnumberbypathways();
            //dump ($vector);die;
            $pcategoriesc = [];
            $pdatac = [];
            foreach ($vector as $values) {
                $pcategoriesc[] = [$values['vector_name']];
                
                $a4 = [$values['vector_name'], $values['count']];
                array_push($pdatac, $a4);
            }
            //dump($a4); die;
            $ob9 = new Highchart();
            $ob9->lang->noData("No Data to display");
            $ob9->chart->renderTo('barchart3');
            $ob9->title->text('NIS per Pathway/Vector');
            $ob9->xAxis->categories($pcategoriesc);
            $ob9->yAxis->title(['text' => 'Numbre of NIS']);
            $ob9->yAxis->allowDecimals(false);
            $ob9->title->style(
                ['fontFamily' => 'Roboto light', 'fontSize' => '18px', 'color' => '#00AEEF', 'fontWeight' => 'bold']
            );
            $ob9->credits->text('www.mamias.org');
            $ob9->credits->href('http://www.mamias.org _target="blank"');
            //$ob5->legend->enabled(true);
            $ob9->labels->enabled(true);
            //$data = array($status,$results2);
            $ob9->series ([['type' => 'bar', 'name' => 'Number of NIS', 'color' => '#00AEEF', 'data' => $pdatac]]);
            
            return $this->render(
                'medlevel/index.html.twig',
                [
                    
                    'piechart1' => $ob3,
                    'piechart2' => $ob4,
                    'barchart1' => $ob5,
                    'piechart3' => $ob6,
                    'total' => $Species_catalogues,
                    'established' => $Species_established,
                    'invasive' => $Species_invasive,
                    'barchart2' => $ob7,
                    'linechart' => $ob8,
                    'barchart3' => $ob9,
                
                ]
            );
        }
    }
