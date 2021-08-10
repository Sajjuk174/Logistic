<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
use Bitrix\Main\Loader;

Loader::includeModule("iblock");
Loader::includeModule("highloadblock");
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

if($USER->IsAuthorized()){

    if($request->getQuery('edit')){
        $APPLICATION->SetPageProperty("TITLE", "Редактирование заявки");
    }else{
        $APPLICATION->SetPageProperty("TITLE", "Добавление заявки");
    }

    //права по группам
    $group = new AccessUser($USER->GetID());
    $filials[USER]=$group->getFilial();


    //подключаю класс с ошибками
    $errors = new PrintError;

    //удалим пробелы в сумме
    if($request->getQueryPost('PRICE')){
        $price=str_replace(' ', '', $request->getQueryPost('PRICE'));
    }

    if($request->getQuery('edit')){
        //проверяем на существование записи
        if(CIBlockElement::GetList(array(), array('IBLOCK_ID' => 3, '=ID' => $request->getQuery('edit')))->Fetch()) {
            //берем запись для редактирования
            $arSelect = Array("ID", "NAME", "DATE_CREATE", "CREATED_BY", "PROPERTY_CNTAM", "PROPERTY_PRODUCTS", "PROPERTY_PRICE", "PROPERTY_PLOHADKA", "PROPERTY_DATAZ", "PROPERTY_PLANTO", "PROPERTY_PLANFROM", "PROPERTY_STATUS", "PROPERTY_CATEGORY", "PROPERTY_TYPE", "PROPERTY_ZAKAZCHIK", "PROPERTY_MASSA", "PROPERTY_NAMECONTAKT", "PROPERTY_PHONECONTACT", "PROPERTY_COMMENT", "PROPERTY_FAKTTO", "PROPERTY_FAKRFROM", "PROPERTY_DOVER", "PROPERTY_NZAKAZ", "PROPERTY_AMFAKT", "PROPERTY_PRIORITY", "PROPERTY_LOGISTAPP", "PROPERTY_IDAPP");
            $arFilter = Array("IBLOCK_ID"=>3, "ID"=>$request->getQuery('edit'));
            $res = CIBlockElement::GetList(Array("SORT" => "ASC"), $arFilter, false, Array(), $arSelect);
            while($ob = $res->GetNextElement()){
                $arOb = $ob->GetFields();
            }

            //филиал кто добавил
            $filial = new AccessUser;
            $filial->setUserId($arOb[CREATED_BY]);
            $filials[ADD]=$filial->getFilial();

        } else{
            header('Location: /add.php?errorid=1');
        }

        //поиск доп. площадок
        $hlbl = 11;
        $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        $rsDatas = $entity_data_class::getList(array(
                "select" => array("*"),
                "order" => array("ID" => "ASC"),
                "filter" => array("UF_OBJECT"=>$request->getQuery('edit')))
        );
        while($arDatas = $rsDatas->Fetch()){
            if($arDatas){
                $IDplohadka=$arDatas[ID];
                $arResult['PLOHADKA']=explode(",", $arDatas[UF_PLOHADKI]);
            }
        }
        //количество площадок закрепленные за заявкой
        if($arResult['PLOHADKA'])$cntpl=count($arResult['PLOHADKA']);

    }





    //обработка формы
    if($request->isPost()){

        //Редактирование формы
        if($request->getQueryPost('ID')){

            $el = new CIBlockElement;
            $iblock_id = 3;
            $section_id = false;
            $PROP = array();
            $PROP['CNTAM'] = $request->getQueryPost('CNTAM');
            $PROP['PRODUCTS'] = $request->getQueryPost('PRODUCTS');
            $PROP['PRICE'] = $price;
            $PROP['PRICE'] = $arOb[PROPERTY_IDAPP_VALUE];
            $PROP['PLOHADKA'] = $request->getQueryPost('PLOHADKA');
            $PROP['DATAZ'] = $request->getQueryPost('DATAZ');
            $PROP['PLANTO'] = $request->getQueryPost('PLANTO');
            $PROP['PLANFROM'] = $request->getQueryPost('PLANFROM');
            $PROP['STATUS'] = $request->getQueryPost('STATUS');
            $PROP['AMFAKT'] = $request->getQueryPost('AMFAKT');
            $PROP['CATEGORY'] = $request->getQueryPost('CATEGORY');
            $PROP['TYPE'] = $request->getQueryPost('TYPE');
            $PROP['ZAKAZCHIK'] = $request->getQueryPost('ZAKAZCHIK');
            $PROP['MASSA'] = $request->getQueryPost('MASSA');
            $PROP['NAMECONTAKT'] = $request->getQueryPost('NAMECONTAKT');
            $PROP['PHONECONTACT'] = $request->getQueryPost('PHONECONTACT');
            $PROP['COMMENT'] = $request->getQueryPost('COMMENT');
            $PROP['CATEGORY'] = $request->getQueryPost('CATEGORY');
            $PROP['FAKTTO'] = $request->getQueryPost('FAKTTO');
            $PROP['FAKRFROM'] = $request->getQueryPost('FAKRFROM');
            $PROP['DOVER'] = $request->getQueryPost('DOVER');
            $PROP['NZAKAZ'] = $request->getQueryPost('NZAKAZ');
            $PROP['PRIORITY'] = $request->getQueryPost('PRIORITY');
            $PROP['LOGISTAPP'] = $request->getQueryPost('LOGISTAPP');
            $PROP['OSP'] = $request->getQueryPost('OSP');

            if($request->getQueryPost('FINISH')){$finish=explode(",", $request->getQueryPost('FINISH'));}
            $PROP['FINISH'] = $finish;
            $fields = array(
                "MODIFIED_BY" => $GLOBALS['USER']->GetID(),
                "TIMESTAMP_X" => date("d.m.Y H:i:s"),
                "IBLOCK_ID" => $iblock_id,
                "PROPERTY_VALUES" => $PROP,
                "NAME" => strip_tags($request->getQueryPost('NAME')),
                "ACTIVE" => "Y",
            );
            $ID = $el->Update($request->getQueryPost('ID'), $fields);

            //есла запись обновлена, обновим площадки и историю полей
            if($ID){

                //формируем историю
                if($arOb['PROPERTY_CNTAM_VALUE']!=$request->getQueryPost('CNTAM')){
                    $fieldto="<b>Количество а/м:</b> ".$arOb['PROPERTY_CNTAM_VALUE']."<br>";
                    $fieldfrom="<b>Количество а/м:</b> ".$request->getQueryPost('CNTAM')."<br>";
                }
                if($arOb['CREATED_BY']!=$request->getQueryPost('CREATED')){
                    $fieldto="<b>Ответсвенный:</b> ".$arOb['CREATED_BY']."<br>";
                    $fieldfrom="<b>Ответсвенный:</b> ".$request->getQueryPost('CREATED')."<br>";
                }
                if($arOb['PROPERTY_PRODUCTS_VALUE']['TEXT']!=$request->getQueryPost('PRODUCTS')){
                    $fieldto.="<b>Продукция:</b> ".$arOb['PROPERTY_PRODUCTS_VALUE']['TEXT']."<br>";
                    $fieldfrom.="<b>Продукция:</b> ".$request->getQueryPost('PRODUCTS')."<br>";
                }
                if($arOb['PROPERTY_MASSA_VALUE']['TEXT']!=$request->getQueryPost('MASSA')){
                    $fieldto.="<b>Требования к автотранспорту:</b> ".$arOb['PROPERTY_MASSA_VALUE']['TEXT']."<br>";
                    $fieldfrom.="<b>Требования к автотранспорту:</b> ".$request->getQueryPost('MASSA')."<br>";
                }
                if($arOb['PROPERTY_PRICE_VALUE']!=$price){
                    $fieldto.="<b>Цена за 1 а/м:</b> ".$arOb['PROPERTY_PRICE_VALUE']."<br>";
                    $fieldfrom.="<b>Цена за 1 а/м:</b> ".$price."<br>";
                }
                if($arOb['PROPERTY_PLOHADKA_ENUM_ID']!=$request->getQueryPost('PLOHADKA')){
                    $res = CIBlock::GetProperties(3);
                    while($res_arr = $res->Fetch()){
                        if($res_arr['CODE'] == "PLOHADKA"){
                            $property_enums = CIBlockPropertyEnum::GetList(Array(), Array("IBLOCK_ID"=>3, "CODE"=>"PLOHADKA","ID"=>$request->getQueryPost('PLOHADKA')));
                            while($enum_fields = $property_enums->GetNext()){
                                $PLOHADKA=$enum_fields['VALUE'];
                            }
                        }
                    }
                    $fieldto.="<b>Площадка отгрузки:</b> ".$arOb['PROPERTY_PLOHADKA_VALUE']."<br>";
                    $fieldfrom.="<b>Площадка отгрузки:</b> ".$PLOHADKA."<br>";
                }
                if($arOb['PROPERTY_PRIORITY_ENUM_ID']!=$request->getQueryPost('PRIORITY')){
                    $res = CIBlock::GetProperties(3);
                    while($res_arr = $res->Fetch()){
                        if($res_arr['CODE'] == "PRIORITY"){
                            $property_enums = CIBlockPropertyEnum::GetList(Array(), Array("IBLOCK_ID"=>3, "CODE"=>"PRIORITY","ID"=>$request->getQueryPost('PRIORITY')));
                            while($enum_fields = $property_enums->GetNext()){
                                $PRIORITY=$enum_fields['VALUE'];
                            }
                        }
                    }
                    $fieldto.="<b>Приоритет:</b> ".$arOb['PROPERTY_PRIORITY_VALUE']."<br>";
                    $fieldfrom.="<b>Приоритет:</b> ".$PRIORITY."<br>";
                }
                if($arOb['PROPERTY_DATAZ_VALUE']!=$request->getQueryPost('DATAZ')){
                    $fieldto.="<b>График работы площадки (заказчик):</b> ".$arOb['PROPERTY_DATAZ_VALUE']."<br>";
                    $fieldfrom.="<b>График работы площадки (заказчик)</b> ".$request->getQueryPost('DATAZ')."<br>";
                }
                if($arOb['PROPERTY_PLANTO_VALUE']!=$request->getQueryPost('PLANTO')){
                    $fieldto.="<b>План дата погрузки с:</b> ".$arOb['PROPERTY_PLANTO_VALUE']."<br>";
                    $fieldfrom.="<b>План дата погрузки с</b> ".$request->getQueryPost('PLANTO')."<br>";
                }
                if($arOb['PROPERTY_PLANFROM_VALUE']!=$request->getQueryPost('PLANFROM')){
                    $fieldto.="<b>План дата погрузки с:</b> ".$arOb['PROPERTY_PLANFROM_VALUE']."<br>";
                    $fieldfrom.="<b>План дата погрузки с:</b> ".$request->getQueryPost('PLANFROM')."<br>";
                }
                if($arOb['PROPERTY_TYPE_ENUM_ID']!=$request->getQueryPost('TYPE')){
                    $res = CIBlock::GetProperties(3);
                    while($res_arr = $res->Fetch()){
                        if($res_arr['CODE'] == "TYPE"){
                            $property_enums = CIBlockPropertyEnum::GetList(Array(), Array("IBLOCK_ID"=>3, "CODE"=>"TYPE","ID"=>$request->getQueryPost('TYPE')));
                            while($enum_fields = $property_enums->GetNext()){
                                $TYPE=$enum_fields['VALUE'];
                            }
                        }
                    }
                    $fieldto.="<b>Вид заявки:</b> ".$arOb['PROPERTY_TYPE_VALUE']."<br>";
                    $fieldfrom.="<b>Вид заявки:</b> ".$TYPE."<br>";
                }
                if($arOb['PROPERTY_ZAKAZCHIK_VALUE']!=$request->getQueryPost('ZAKAZCHIK')){
                    $fieldto.="<b>Компания:</b> ".$arOb['PROPERTY_ZAKAZCHIK_VALUE']."<br>";
                    $fieldfrom.="<b>Компания:</b> ".$request->getQueryPost('ZAKAZCHIK')."<br>";
                }
                if($arOb['PROPERTY_NAMECONTAKT_VALUE']!=$request->getQueryPost('NAMECONTAKT')){
                    $fieldto.="<b>Контактное лицо:</b> ".$arOb['PROPERTY_NAMECONTAKT_VALUE']."<br>";
                    $fieldfrom.="<b>Контактное лицо:</b> ".$request->getQueryPost('NAMECONTAKT')."<br>";
                }
                if($arOb['PROPERTY_PHONECONTACT_VALUE']!=$request->getQueryPost('PHONECONTACT')){
                    $fieldto.="<b>Телефон:</b> ".$arOb['PROPERTY_PHONECONTACT_VALUE']."<br>";
                    $fieldfrom.="<b>Телефон:</b> ".$request->getQueryPost('PHONECONTACT')."<br>";
                }
                if($arOb['PROPERTY_FAKTTO_VALUE']!=$request->getQueryPost('FAKTTO')){
                    $fieldto.="<b>План дата выгрузки с:</b> ".$arOb['PROPERTY_FAKTTO_VALUE']."<br>";
                    $fieldfrom.="<b>План дата выгрузки с:</b> ".$request->getQueryPost('FAKTTO')."<br>";
                }
                if($arOb['PROPERTY_FAKRFROM_VALUE']!=$request->getQueryPost('FAKRFROM')){
                    $fieldto.="<b>План дата выгрузки до:</b> ".$arOb['PROPERTY_FAKRFROM_VALUE']."<br>";
                    $fieldfrom.="<b>План дата выгрузки до:</b> ".$request->getQueryPost('FAKRFROM')."<br>";
                }
                if($arOb['PROPERTY_NZAKAZ_VALUE']!=$request->getQueryPost('NZAKAZ')){
                    $fieldto.="<b>№ заказа покупателя:</b> ".$arOb['PROPERTY_NZAKAZ_VALUE']."<br>";
                    $fieldfrom.="<b>№ заказа покупателя:</b> ".$request->getQueryPost('NZAKAZ')."<br>";
                }
                if($arOb['PROPERTY_COMMENT_VALUE']['TEXT']!=$request->getQueryPost('COMMENT')){
                    $fieldto.="<b>Комментарий:</b> ".$arOb['PROPERTY_COMMENT_VALUE']['TEXT']."<br>";
                    $fieldfrom.="<b>Комментарий:</b> ".$request->getQueryPost('COMMENT')."<br>";
                }
                if($arOb['PROPERTY_DOVER_ENUM_ID']!=$request->getQueryPost('DOVER')){
                    $res = CIBlock::GetProperties(3);
                    while($res_arr = $res->Fetch()){
                        if($res_arr['CODE'] == "DOVER"){
                            $property_enums = CIBlockPropertyEnum::GetList(Array(), Array("IBLOCK_ID"=>3, "CODE"=>"DOVER","ID"=>$request->getQueryPost('DOVER')));
                            while($enum_fields = $property_enums->GetNext()){
                                $DOVER=$enum_fields['VALUE'];
                            }
                        }
                    }
                    $fieldto.="<b>Доверенность / печать:</b> ".$arOb['PROPERTY_DOVER_VALUE']."<br>";
                    $fieldfrom.="<b>Доверенность / печать:</b> ".$DOVER."<br>";
                }

                if(($fieldto)&&($fieldfrom)){
                    //добавление истории изменения полей
                    $hlbl = 4;
                    $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();
                    $entity = HL\HighloadBlockTable::compileEntity($hlblock);
                    $entity_data_class = $entity->getDataClass();
                    $data = array(
                        'UF_USER' => $GLOBALS['USER']->GetID(),
                        'UF_DATE' => date("d.m.Y H:i:s"),
                        'UF_OBJECTS' => $request->getQueryPost('ID'),
                        'UF_IBLOCK' => 3,
                        'UF_FIELDTO' => $fieldto,
                        'UF_FIELDFROM' => $fieldfrom
                    );
                    $result = $entity_data_class::add($data);
                }

                //обновления для доп. площадок
                if(($request->getQueryPost('PLOHADKAS'))&&($IDplohadka)){
                    $plohadka = implode(",", $request->getQueryPost('PLOHADKAS'));
                    $hlbl = 11;
                    $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();
                    $entity = HL\HighloadBlockTable::compileEntity($hlblock);
                    $entity_data_classs = $entity->getDataClass();
                    $data = array(
                        'UF_PLOHADKI' => $plohadka
                    );
                    $entity_data_classs::update($IDplohadka, $data);
                }elseif($IDplohadka){
                    $hlbl = 11;
                    $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();
                    $entity = HL\HighloadBlockTable::compileEntity($hlblock);
                    $entity_data_class = $entity->getDataClass();
                    $entity_data_class::Delete($IDplohadka);
                }elseif($request->getQueryPost('PLOHADKAS')){
                    $plohadka = implode(",", $request->getQueryPost('PLOHADKAS'));
                    $hlbl = 11;
                    $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();
                    $entity = HL\HighloadBlockTable::compileEntity($hlblock);
                    $entity_data_class = $entity->getDataClass();
                    $data = array(
                        'UF_PLOHADKI' => $plohadka,
                        'UF_OBJECT' => $request->getQueryPost('ID')
                    );
                    $result = $entity_data_class::add($data);
                }


                echo "<div class='alert alert-success' role='alert'><h4 style='font-size:18px !important'>Заявка <strong>«".$request->getQueryPost(		'NAME')."»</strong> обновлена!</h4>
					<a href='/".$ID."/' class='btn btn-success'>Прейти в заявку</a>
					<a href='/add.php?edit=".$ID."' class='btn btn-success'>Редактировать заявку</a>
					</div>";

            }



        }else{
            //добавление новой записи
            //проверим на схожесть
            if (CIBlockElement::GetList(array(), array('IBLOCK_ID' => 3, '=NAME' => $request->getQueryPost('NAME'), 'PROPERTY_ZAKAZCHIK' => $request->	getPost('ZAKAZCHIK'), '=PROPERTY_PLANTO' => ConvertDateTime($request->getQueryPost('PLANTO'), "YYYY-MM-DD"), '=PROPERTY_PLANFROM' => ConvertDateTime($request->getQueryPost('PLANFROM'), "YYYY-MM-DD")))->Fetch()){
                echo "<div class='alert alert-danger' role='alert'><h5><strong>Ошибка!</strong> Объект уже добавлен!</h5></div>";
            }else{

                $el = new CIBlockElement;
                $iblock_id = 3;
                $section_id = false;
                $PROP = array();
                $PROP['CNTAM'] = $request->getQueryPost('CNTAM');
                $PROP['PRODUCTS'] = $request->getQueryPost('PRODUCTS');
                $PROP['PRICE'] = $price;
                $PROP['IDAPP'] = $request->getQueryPost('IDAPP');
                $PROP['PLOHADKA'] = $request->getQueryPost('PLOHADKA');
                $PROP['DATAZ'] = $request->getQueryPost('DATAZ');
                $PROP['PLANTO'] = $request->getQueryPost('PLANTO');
                $PROP['PLANFROM'] = $request->getQueryPost('PLANFROM');
                $PROP['STATUS'] = 2;
                $PROP['AMFAKT'] = 0;
                $PROP['CATEGORY'] = $request->getQueryPost('CATEGORY');
                $PROP['TYPE'] = $request->getQueryPost('TYPE');
                $PROP['ZAKAZCHIK'] = $request->getQueryPost('ZAKAZCHIK');
                $PROP['MASSA'] = $request->getQueryPost('MASSA');
                $PROP['NAMECONTAKT'] = $request->getQueryPost('NAMECONTAKT');
                $PROP['PHONECONTACT'] = $request->getQueryPost('PHONECONTACT');
                $PROP['COMMENT'] = $request->getQueryPost('COMMENT');
                $PROP['FAKTTO'] = $request->getQueryPost('FAKTTO');
                $PROP['FAKRFROM'] = $request->getQueryPost('FAKRFROM');
                $PROP['DOVER'] = $request->getQueryPost('DOVER');
                $PROP['NZAKAZ'] = $request->getQueryPost('NZAKAZ');
                $PROP['PRIORITY'] = $request->getQueryPost('PRIORITY');
                $PROP['OSP'] = $request->getQueryPost('OSP');
                if($request->getQueryPost('OSP')){
                    $PROP['LOGISTAPP'] = 17;
                }

                $fields = array(
                    "DATE_CREATE" => date("d.m.Y H:i:s"),
                    "CREATED_BY" => $GLOBALS['USER']->GetID(),
                    "IBLOCK_ID" => $iblock_id,
                    "PROPERTY_VALUES" => $PROP,
                    "NAME" => strip_tags($request->getQueryPost('NAME')),
                    "ACTIVE" => "Y",
                );
                $ID = $el->Add($fields);

                //если заявка не предворительная, отправим письмо транспортным компаниям
                if(($request->getQueryPost('TYPE')!=20)&&($ID)){
                    //метод собирает почты компаний по направлению
                    if($group->getCmail($request->getQueryPost('CATEGORY'))){
                        //пишем письма трнаспортным
                        $emailto=implode(",", $group->getCmail($request->getQueryPost('CATEGORY')));
                        $subject="ГК «СТРОЙСИСТЕМА» добавлена новая заявка";
                        $text="<p style='font-family: arial;'>Здравствуйте! <b>По Вашему направлению</b> добавлена новая заявка! </p>";
                        $text.="<a href='/".$ID."/' style='font-family: arial;background: #004d9f;color: #fff;padding: 10px;border-radius: 4px;'><span style='padding:10px;'>Подробнее</span></a>";
                        $arEventFields = array('SUBJECTS' => $subject, 'EMAILTO' => $emailto, 'TEXT' => $text);
                        CEvent::Send("SET_CATEGORY", SITE_ID, $arEventFields);
                    }
                }

                if(($request->getQueryPost('PLOHADKAS'))&&($ID)){
                    //добавляем доп.площадки
                    $plohadka = implode(",", $request->getQueryPost('PLOHADKAS'));
                    $hlbl = 11;
                    $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();
                    $entity = HL\HighloadBlockTable::compileEntity($hlblock);
                    $entity_data_class = $entity->getDataClass();
                    $data = array(
                        'UF_PLOHADKI' => $plohadka,
                        'UF_OBJECT' => $ID
                    );
                    $result = $entity_data_class::add($data);
                }

                if($ID){
                    //добавление истории создания
                    $hlbl = 4;
                    $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();
                    $entity = HL\HighloadBlockTable::compileEntity($hlblock);
                    $entity_data_class = $entity->getDataClass();
                    $data = array(
                        'UF_USER' => $GLOBALS['USER']->GetID(),
                        'UF_DATE' => date("d.m.Y H:i:s"),
                        'UF_OBJECTS' => $ID,
                        'UF_IBLOCK' => $iblock_id,
                        'UF_FIELDTO' => "Дата создания заявки",
                        'UF_FIELDFROM' => ""
                    );
                    $result = $entity_data_class::add($data);

                    echo "<div class='alert alert-success' role='alert'><h4 style='font-size:18px !important'>Заявка <strong>«".$request->getQueryPost('NAME')."»</strong> добавлена!</h4>
				<a href='/".$ID."/' class='btn btn-success'>Прейти в заявку</a>
				<a href='/add.php?edit=".$ID."' class='btn btn-success'>Редактировать заявку</a>
				</div>";

                }
            }

        }

    }

    ?>




    <?if($request->getQuery('errorid')){
        $errors->getAdd($request->getQuery('errorid'));
    }else{
        //если нет ошибок показываем форму
        ?>

        <?
        if($group->getGroup()=="admin"){

            $access=true;

        }elseif ($group->getGroup()=="boss"){

            $access=true;

        }elseif ($group->getGroup()=="sbyt"){

            if($request->getQueryQuery('edit')){

                if($arOb[CREATED_BY]==$USER->GetID()){
                    $access=true;

                }else{

                    $access=false;
                    header('Location: /add.php?errorid=2');

                }
            }else{
                $access=true;
            }

        }else{
            $access=false;
            header('Location: /?errorid=0');
        }
        ?>

        <div id="add_my_ankete">

            <div id="errorvalid"></div>
            <h3>Погрузка</h3>
            <p><span class="required">*</span> - отмеченный обязательные поля для заполнения.</p>
            <form name="add_my_ankete" action="<?=POST_FORM_ACTION_URI?>" method="POST" id="formadd" enctype="multipart/form-data" class="formadd needs-validation" novalidate>


                <div class="rows align-items-center">
                    <div class="col-sm-12 col-md-2 col-lg-4">
                        Вид заявки <span class="required">*</span>:
                    </div>
                    <div class="col-sm-12 col-md-10 col-lg-8">
                        <?if($request->getQuery('edit')){?>
                            <input type="hidden" name="EDIT" value="<?=$arOb["ID"]?>">
                            <input type="hidden" name="STATUS" value="<?=$arOb["PROPERTY_STATUS_ENUM_ID"]?>">
                            <input type="hidden" name="ID" value="<?=$arOb["ID"]?>">
                            <input type="hidden" name="AMFAKT" value="<?=$arOb["PROPERTY_AMFAKT_VALUE"]?>">

                            <input type="hidden" name="TYPE" id='typezayavka' value="<?=$arOb["PROPERTY_TYPE_ENUM_ID"]?>">
                            <input type="hidden" name="LOGISTAPP" value="<?=$arOb["PROPERTY_LOGISTAPP_ENUM_ID"]?>">

                            <select name='noform' class="form-control select2" disabled>
                                <option value="">Вид заявки</option>
                                <?
                                $IBLOCK_ID =3;
                                $res = CIBlock::GetProperties($IBLOCK_ID);
                                while($res_arr = $res->Fetch()){
                                    if($res_arr['CODE'] == "TYPE"){
                                        $property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$IBLOCK_ID, "CODE"=>"TYPE"));
                                        while($enum_fields = $property_enums->GetNext())
                                        {

                                            echo "<option value='".$enum_fields['ID']."' ".(($arOb["PROPERTY_TYPE_ENUM_ID"]==$enum_fields['ID'])?"selected":"").">".$enum_fields['VALUE']."</option>";

                                        }
                                    }
                                }
                                ?>
                            </select>
                            <p class='t-s-error'>В режиме редактирования запрещено менять вид заявки!</p>

                        <?}else{?>
                            <select name='TYPE' id='typezayavka' class="form-control select2" required>
                                <option value="">Вид заявки</option>
                                <?
                                $IBLOCK_ID =3;
                                $res = CIBlock::GetProperties($IBLOCK_ID);
                                while($res_arr = $res->Fetch()){
                                    if($res_arr['CODE'] == "TYPE"){
                                        $property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$IBLOCK_ID, "CODE"=>"TYPE"));
                                        while($enum_fields = $property_enums->GetNext())
                                        {
                                            if($group->osp()==1){
                                                if($enum_fields['ID']!=20)
                                                    echo "<option value='".$enum_fields['ID']."' ".(($arOb["PROPERTY_TYPE_ENUM_ID"]==$enum_fields['ID'])?"selected":"").">".$enum_fields['VALUE']."</option>";
                                            }else{
                                                echo "<option value='".$enum_fields['ID']."' ".(($arOb["PROPERTY_TYPE_ENUM_ID"]==$enum_fields['ID'])?"selected":"").">".$enum_fields['VALUE']."</option>";
                                            }
                                        }
                                    }
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">
                                Выберите «вид заявки»
                            </div>
                        <?}?>
                    </div>
                </div>


                <?if($group->osp()!=true){?>
                    <div class="rows align-items-center">
                        <div class="col-sm-12 col-md-2 col-lg-4">
                            Площадка отгрузки <span class="required">*</span>:
                        </div>
                        <div class="col-sm-12 col-md-10 col-lg-8">
                            <select name='PLOHADKA' class="form-control select2" required>
                                <option value="">Площадка отгрузки</option>
                                <?
                                $IBLOCK_ID =3;
                                $res = CIBlock::GetProperties($IBLOCK_ID);
                                while($res_arr = $res->Fetch()){
                                    if($res_arr['CODE'] == "PLOHADKA"){
                                        $property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$IBLOCK_ID, "CODE"=>"PLOHADKA"));
                                        while($enum_fields = $property_enums->GetNext())
                                        {
                                            echo "<option value='".$enum_fields['ID']."' ".(($arOb["PROPERTY_PLOHADKA_ENUM_ID"]==$enum_fields['ID'])?"selected":"").">".$enum_fields['VALUE']."</option>";
                                        }
                                    }
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">
                                Выберите «Площадка отгрузки»
                            </div>

                            <div id='TextBoxesGroup'>
                                <?if($arResult['PLOHADKA']){
                                    //если есть доп лощадки
                                    $numpl=0;
                                    foreach($arResult['PLOHADKA'] as $plohadka){
                                        $numpl++;
                                        ?>
                                        <div id='TextBoxDiv<?=$numpl?>'>
                                            <select name='PLOHADKAS[]' class="form-control select2" required>
                                                <option value="">Площадка отгрузки</option>
                                                <?
                                                $IBLOCK_ID =3;
                                                $res = CIBlock::GetProperties($IBLOCK_ID);
                                                while($res_arr = $res->Fetch()){
                                                    if($res_arr['CODE'] == "PLOHADKA"){
                                                        $property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$IBLOCK_ID, "CODE"=>"PLOHADKA"));
                                                        while($enum_fields = $property_enums->GetNext())
                                                        {
                                                            echo "<option value='".$enum_fields['ID']."' ".(($plohadka==$enum_fields['ID'])?"selected":"").">".$enum_fields['VALUE']."</option>";
                                                        }
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <?
                                    }
                                }else{
                                    echo "<div id='TextBoxDiv1'></div>";
                                }
                                ?>
                            </div>

                                <div id='BoxesScript'>
                                    <div id="DivScript1"></div>
                                </div>
                                <input type='button' value='Добавить площадку' id='addButton'>
                                <input type='button' value='Удалить площадку' id='removeButton'>

                                <script type="text/javascript">
                                    $(document).ready(function(){
                                        var counter = <?=(($cntpl)? $cntpl+1:"2")?>;
                                        var counter2 = <?=(($cntpl)? $cntpl+1:"2")?>;
                                        $("#addButton").click(function () {
                                            if(counter>5){
                                                alert("Лимит не более 5 площадок");
                                                return false;
                                            }
                                            var newTextBoxDiv = $(document.createElement('div'))
                                                .attr("id", 'TextBoxDiv' + counter);
                                            newTextBoxDiv.after().html('<select name="PLOHADKAS[]" class="form-control select2" id="laborList'+ counter+'"><option value="">Площадка отгрузки #'+ counter+'</option><?php
                                                $IBLOCK_ID =3;
                                                $res = CIBlock::GetProperties($IBLOCK_ID);
                                                while($res_arr = $res->Fetch()){
                                                    if($res_arr['CODE'] == "PLOHADKA"){
                                                        $property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$IBLOCK_ID, "CODE"=>"PLOHADKA"));
                                                        while($enum_fields = $property_enums->GetNext()){
                                                            echo "<option value=".$enum_fields['ID']." >".$enum_fields['VALUE']."</option>";
                                                        }
                                                    }
                                                }?></select>');
                                            newTextBoxDiv.appendTo("#TextBoxesGroup");
                                            counter++;
                                            var newTextBoxDiv = $(document.createElement('script'))
                                                .attr("id", 'DivScript' + counter);
                                            newTextBoxDiv.after().html('$(document).ready(function(){$( ".select2" ).select2( { placeholder: "", language: "ru", allowClear: true} );});');
                                            newTextBoxDiv.appendTo("#BoxesScript");
                                            counter2++;
                                        });

                                        $("#removeButton").click(function () {
                                            if(counter==1){
                                                return false;
                                            }
                                            counter--;
                                            counter2--;
                                            $("#DivScript" + counter2).remove();
                                            $("#TextBoxDiv" + counter).remove();
                                        });
                                    });
                                </script>
                           
                        </div>
                    </div>
                <?}else{?>
                    <input type="hidden" name="PLOHADKA" value="27">
                <?}?>

                <div class="rows align-items-center">
                    <div class="col-sm-12 col-md-2 col-lg-4">
                        План дата погрузки с - до (ДД.ММ.ГГГГ) <span class="required noreq">*</span>:
                    </div>
                    <div class="col-sm-12 col-md-10 col-lg-8 p-0">
                        <div class="adddate">
                            <?$APPLICATION->IncludeComponent(
                                "bitrix:main.calendar",
                                "adddate",
                                array(
                                    "FORM_NAME" => "",
                                    "HIDE_TIMEBAR" => "Y",
                                    "INPUT_NAME" => "PLANTO",
                                    "INPUT_NAME_FINISH" => "PLANFROM",
                                    "INPUT_VALUE" => $arOb['PROPERTY_PLANTO_VALUE'],
                                    "INPUT_VALUE_FINISH" => $arOb['PROPERTY_PLANFROM_VALUE'],
                                    "SHOW_INPUT" => "Y",
                                    "SHOW_TIME" => "N",
                                    "COMPONENT_TEMPLATE" => ""
                                ),
                                false
                            );?>
                        </div>
                    </div>
                </div>

                <div class="rows align-items-center">
                    <div class="col-sm-12 col-md-2 col-lg-4">
                        Продукция
                    </div>
                    <div class="col-sm-12 col-md-10 col-lg-8">
                        <textarea name="PRODUCTS" placeholder="Продукция" class="form-control" style="width:100%;min-height:80px;"><?=$arOb['PROPERTY_PRODUCTS_VALUE']['TEXT']?></textarea>
                        <div class="invalid-feedback">
                            Заполните поле «Продукция»
                        </div>

                    </div>
                </div>

                <div class="rows align-items-center">
                    <div class="col-sm-12 col-md-2 col-lg-4">
                        Требования к автотранспорту
                    </div>
                    <div class="col-sm-12 col-md-10 col-lg-8">
                        <textarea name="MASSA" placeholder="Требования к автотранспорту" class="form-control" style="width:100%;min-height:80px;"><?=$arOb['PROPERTY_MASSA_VALUE']['TEXT']?></textarea>
                        <div class="invalid-feedback">
                            Заполните поле «Требования к автотранспорту»
                        </div>
                    </div>
                </div>



                <h3>Выгрузка</h3>
                <div class="rows align-items-center">
                    <div class="col-sm-12 col-md-2 col-lg-4">
                        Фактический адрес выгрузки<span class="required">*</span>:
                    </div>
                    <div class="col-sm-12 col-md-10 col-lg-8">
                        <input type="text" name="NAME" maxlength="255" id='adres' value="<?=$arOb['NAME']?>" class="form-control" required>
                        <div class="invalid-feedback">
                            Заполните поле «Фактический адрес выгрузки»
                        </div>
                    </div>
                </div>

                <?if($group->osp()!=true){?>
                    <div class="rows align-items-center" id='divcomp'>
                        <div class="col-sm-12 col-md-2 col-lg-4">
                            Компания<span class="required">*</span>:
                        </div>
                        <div class="col-sm-12 col-md-10 col-lg-8">
                            <input type="text" name="ZAKAZCHIK" id="comp" maxlength="255" value="<?=$arOb['PROPERTY_ZAKAZCHIK_VALUE']?>" class="form-control" required >
                            <div class="invalid-feedback">
                                Заполните поле «Компания»
                            </div>
                        </div>
                    </div>
                <?}?>

                <div class="rows align-items-center">
                    <div class="col-sm-12 col-md-2 col-lg-4">
                        Контактное лицо<span class="required noreq">*</span>:
                    </div>
                    <div class="col-sm-12 col-md-10 col-lg-8 p-0">
                        <div class="rows p-0" style="background:#eaeaea">
                            <div class="col">
                                <input type="text" name="NAMECONTAKT" placeholder="ФИО" id='fio' maxlength="255" value="<?=$arOb['PROPERTY_NAMECONTAKT_VALUE']?>" class="form-control" required>
                            </div>
                            <div class="col">
                                <input type="text" name="PHONECONTACT" placeholder="Телефон" id='phone' maxlength="255" value="<?=$arOb['PROPERTY_PHONECONTACT_VALUE']?>" class="form-control" required>
                            </div>
                        </div>
                        <div class="invalid-feedback">
                            Заполните поле «Контактное лицо»
                        </div>
                    </div>
                </div>

                <div class="rows align-items-center">
                    <div class="col-sm-12 col-md-2 col-lg-4">
                        Количество а/м / цена<span class="required">*</span>:
                    </div>
                    <div class="col-sm-12 col-md-10 col-lg-8 p-0">
                        <div class="rows p-0" style="background: #f7f7f7;">
                            <div class="col">
                                <input type="text" name="CNTAM" placeholder="Количество а/м" maxlength="255" value="<?=$arOb['PROPERTY_CNTAM_VALUE']?>" class="form-control" required>
                            </div>
                            <div class="col">
                                <input type="text" name="PRICE" placeholder="Цена за 1 а/м" maxlength="255" value="<?=$arOb['PROPERTY_PRICE_VALUE']?>" class="form-control" required>
                            </div>
                        </div>
                        <div class="invalid-feedback">
                            Заполните поле «Количество а/м / цена»
                        </div>
                    </div>
                </div>

                <?if($group->osp()!=true){?>
                    <div class="rows align-items-center" id='divzakaz'>
                        <div class="col-sm-12 col-md-2 col-lg-4">
                            № заказа покупателя<span class="required noreq">*</span>:
                        </div>
                        <div class="col-sm-12 col-md-10 col-lg-8">
                            <input type="text" name="NZAKAZ" maxlength="255" id='nzakaz' value="<?=$arOb['PROPERTY_NZAKAZ_VALUE']?>" class="form-control" required>
                            <div class="invalid-feedback">
                                Заполните поле «№ заказа покупателя»
                            </div>
                        </div>
                    </div>

                    <div class="rows align-items-center" id='divdover'>
                        <div class="col-sm-12 col-md-2 col-lg-4">
                            Доверенность / печать <span class="required noreq">*</span>:
                        </div>
                        <div class="col-sm-12 col-md-10 col-lg-8">
                            <select name='DOVER' class="form-control select2" id='dover' required>
                                <option value="">Доверенность / печать</option>
                                <?
                                $IBLOCK_ID =3;
                                $res = CIBlock::GetProperties($IBLOCK_ID);
                                while($res_arr = $res->Fetch()){
                                    if($res_arr['CODE'] == "DOVER"){
                                        $property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$IBLOCK_ID, "CODE"=>"DOVER"));
                                        while($enum_fields = $property_enums->GetNext())
                                        {
                                            echo "<option value='".$enum_fields['ID']."' ".(($arOb["PROPERTY_DOVER_ENUM_ID"]==$enum_fields['ID'])?"selected":"").">".$enum_fields['VALUE']."</option>";
                                        }
                                    }
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">
                                Выберите «Доверенность / печать»
                            </div>
                        </div>
                    </div>
                <?}?>

                <div class="rows align-items-center">
                    <div class="col-sm-12 col-md-2 col-lg-4">
                        План дата выгрузки по договору с - до (ДД.ММ.ГГГГ) <span class="required">*</span>:
                    </div>
                    <div class="col-sm-12 col-md-10 col-lg-8 p-0">
                        <div class="adddate">
                            <?$APPLICATION->IncludeComponent(
                                "bitrix:main.calendar",
                                "adddate",
                                array(
                                    "FORM_NAME" => "",
                                    "HIDE_TIMEBAR" => "Y",
                                    "INPUT_NAME" => "FAKTTO",
                                    "INPUT_NAME_FINISH" => "FAKRFROM",
                                    "INPUT_VALUE" => $arOb['PROPERTY_FAKTTO_VALUE'],
                                    "INPUT_VALUE_FINISH" => $arOb['PROPERTY_FAKRFROM_VALUE'],
                                    "SHOW_INPUT" => "Y",
                                    "SHOW_TIME" => "N",
                                    "COMPONENT_TEMPLATE" => ""
                                ),
                                false
                            );?>
                        </div>
                    </div>
                </div>

                <div class="rows align-items-center">
                    <div class="col-sm-12 col-md-2 col-lg-4">
                        График работы площадки<span class="required noreq">*</span>:
                    </div>
                    <div class="col-sm-12 col-md-10 col-lg-8">
                        <input type="text" name="DATAZ" maxlength="255" id='jobpl' value="<?=$arOb['PROPERTY_DATAZ_VALUE']?>" class="form-control" required>
                        <div class="invalid-feedback">
                            Заполните поле «График работы площадки»
                        </div>
                    </div>
                </div>


                <div class="rows align-items-center">
                    <div class="col-sm-12 col-md-2 col-lg-4">
                        Комментарий
                    </div>
                    <div class="col-sm-12 col-md-10 col-lg-8">
                        <textarea name="COMMENT" placeholder="Текст примечания" class="form-control" style="width:100%;min-height:100px;"><?=$arOb['PROPERTY_COMMENT_VALUE']['TEXT']?></textarea>
                        <div class="invalid-feedback">
                            Объязательное поле
                        </div>

                    </div>
                </div>


                <?if($group->osp()!=true){?>
                    <h3>Дополнительно</h3>
                    <div class="rows align-items-center">
                        <div class="col-sm-12 col-md-2 col-lg-4">
                            Выбрать направление<span class="required noreq">*</span>:
                        </div>
                        <div class="col-sm-12 col-md-10 col-lg-8">
                            <select name='CATEGORY' class="form-control select2" id="category" required>
                                <option value="">Выбрать направление</option>
                                <?if($group->getGroup()=="admin"){?><option value="211" <?=(($arOb["PROPERTY_CATEGORY_VALUE"]==211)?"selected":"")?>>ОСП-ЧЗПСН</option><?}?>
                                <?if ($group->getGroup()!="sbyt"){?><option value="10" <?=(($arOb["PROPERTY_CATEGORY_VALUE"]==10)?"selected":"")?>>Все направления</option><?}?>
                                <?
                                $arSelect = Array("ID", "NAME");
                                $arFilter = Array("IBLOCK_ID"=>2, "ACTIVE"=>"Y", "!ID"=>array(10,211));
                                $res = CIBlockElement::GetList(Array("NAME" => "ASC"), $arFilter, false, Array(), $arSelect);
                                while($ob = $res->GetNextElement()){
                                    $arObs = $ob->GetFields();
                                    echo "<option value='".$arObs['ID']."' ".(($arOb["PROPERTY_CATEGORY_VALUE"]==$arObs['ID'])?"selected":"").">".$arObs['NAME']."</option>";
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">
                                Объязательное поле
                            </div>
                        </div>
                    </div>
                <?}else{?>
                    <input type="hidden" name="CATEGORY" value="211">
                    <input type="hidden" name="OSP" value="21">
                <?}?>

                <?if($group->osp()!=true){?>
                    <div class="rows align-items-center">
                        <div class="col-sm-12 col-md-2 col-lg-4">
                            Приоритет:
                        </div>
                        <div class="col-sm-12 col-md-10 col-lg-8">
                            <style type="text/css">
                                .hide{
                                    display:none;
                                }
                                .hide.show{
                                    display: block;
                                }
                            </style>
                            <select name='PRIORITY' class="form-control select2" id='priority'>
                                <option value="">Выбрать приоритет </option>
                                <?
                                $IBLOCK_ID =3;
                                $res = CIBlock::GetProperties($IBLOCK_ID);
                                while($res_arr = $res->Fetch()){
                                    if($res_arr['CODE'] == "PRIORITY"){
                                        $property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$IBLOCK_ID, "CODE"=>"PRIORITY"));
                                        while($enum_fields = $property_enums->GetNext())
                                        {
                                            if($group->getGroup()=="sbyt"){
                                                if($enum_fields['ID']!=16)
                                                    echo "<option value='".$enum_fields['ID']."' ".(($arOb["PROPERTY_PRIORITY_ENUM_ID"]==$enum_fields['ID'])?"selected":"").">".$enum_fields['VALUE']."</option>";
                                            }else{
                                                echo "<option value='".$enum_fields['ID']."' ".(($arOb["PROPERTY_PRIORITY_ENUM_ID"]==$enum_fields['ID'])?"selected":"").">".$enum_fields['VALUE']."</option>";
                                            }
                                        }
                                    }
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">
                                Заполните поле «Приоритет»
                            </div>

                            <div class="alert alert-info" id='alertdanger' style="display:none">
                                <button data-dismiss="alert" class="close" type="button">×</button>
                                <span class="entypo-attention"></span>
                                <strong>Внимание!</strong>&nbsp;&nbsp; стоимость заявки с высоким приоритетом будет показана транспортным компаниям!
                            </div>


                            <script>
                                $(document).ready(function() {
                                    $("#priority").change(function() {
                                        var itemspr = $(this).val();
                                        if(itemspr == 16){
                                            alertdanger.style.display = "block";
                                        }else{
                                            alertdanger.style.display = "none";
                                        }
                                    });

                                    var itemspr = $("#priority").val();
                                    if(itemspr == 16){
                                        alertdanger.style.display = "block";
                                    }else{
                                        alertdanger.style.display = "none";
                                    }

                                });
                            </script>

                        </div>
                    </div>
                <?}?>


                <script>
                    $(document).ready(function() {
                        $("#typezayavka").change(function() {
                            //слушаем событие выбора
                            var itemspr = $(this).val();
                            if(itemspr == 20){
                                fio.removeAttribute('required');
                                phone.removeAttribute('required');
                                nzakaz.removeAttribute('required');
                                dover.removeAttribute('required');
                                jobpl.removeAttribute('required');
                                category.removeAttribute('required');
                                PLANTO.removeAttribute('required');
                                PLANFROM.removeAttribute('required');

                                //скрываем звездочки
                                var allElem = document.querySelectorAll('.noreq');
                                for(var i = 0; i < allElem.length; i++){
                                    allElem[i].classList.toggle('none');
                                }
                            }else{
                                $('#fio').attr('required', '');
                                $('#phone').attr('required', '');
                                $('#nzakaz').attr('required', '');

                                if ($('#osp').is(':checked')){
                                    dover.removeAttribute('required');
                                }else{
                                    $('#dover').attr('required', '');
                                }

                                $('#jobpl').attr('required', '');
                                $('#category').attr('required', '');

                                //скрываем звездочки
                                var allElem = document.querySelectorAll('.noreq');
                                for(var i = 0; i < allElem.length; i++){
                                    allElem[i].classList.toggle('none');
                                }

                            }


                        });

                        //событие при редактировании
                        var itemsprs = $("#typezayavka").val();
                        if(itemsprs == 20){
                            fio.removeAttribute('required');
                            phone.removeAttribute('required');
                            nzakaz.removeAttribute('required');
                            dover.removeAttribute('required');
                            jobpl.removeAttribute('required');
                            category.removeAttribute('required');
                            PLANTO.removeAttribute('required');
                            PLANFROM.removeAttribute('required');

                            //скрываем звездочки
                            var allElem = document.querySelectorAll('.noreq');
                            for(var i = 0; i < allElem.length; i++){
                                allElem[i].classList.toggle('none');
                            }
                        }

                    });
                </script>


                <button type="submit" name="button" value="17" class="btn btn-info">
                    <?if ($request->getQuery('edit')):?><span class="icon icon-clockwise"></span> Обновить
                    <?else:?><span class="entypo-plus-circled"></span> Добавить заявку
                    <?endif;?>
                </button>
            </form>



            <script type="text/javascript">
                (function() {
                    'use strict';
                    window.addEventListener('load', function() {
                        var forms = document.getElementsByClassName('needs-validation');
                        var validation = Array.prototype.filter.call(forms, function(form) {
                            form.addEventListener('submit', function(event) {
                                if (form.checkValidity() === false) {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    document.getElementById("errorvalid").innerHTML = '<div class="alert alert-danger" role="alert"><strong>Ошибка!</strong> Заполните обязательные поля!!!</div>';
                                }
                                form.classList.add('was-validated');
                                window.scrollTo(0, 0);
                            }, false);
                        });
                    }, false);
                })();
            </script>

            <br>
        </div>
    <?}?>
    <script type="text/javascript">
        $(document).ready(function(){
            $( ".select2" ).select2( { placeholder: "", language: "ru", allowClear: true} );
        });
    </script>

<?}else{?>
    <div>
        <?$APPLICATION->IncludeComponent(
            "bitrix:system.auth.form",
            "auth",
            Array(
                "FORGOT_PASSWORD_URL" => "",
                "PROFILE_URL" => "",
                "REGISTER_URL" => "",
                "SHOW_ERRORS" => "N"
            )
        );?><br>
        <noindex>
            <p><a href="/auth/?forgot_password=yes" rel="nofollow">Забыли свой пароль?</a></p>
        </noindex>
    </div>
<?}?>



<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>