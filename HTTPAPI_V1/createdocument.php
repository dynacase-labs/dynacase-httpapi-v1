<?php



function createDocument(Action $action)
{
    $usage = new ActionUsage($action);

    $usage->setText("Create new document");
    $familyId = $usage->addRequiredParameter("family", "Family identifier");


    $return = new \Dcp\HttpApi\recordReturn();




    try {
        $usage->verify(true);
        $family = \Dcp\DocManager::getFamily($familyId);

        $doc = Dcp\DocManager::createDocument($family->id);


        $values = \Dcp\HttpApi\recordParameters::getValues();
        foreach ($values as $attrid => $value) {
            $doc->setAttributeValue($attrid, $value);
        }

        $error=$doc->store($info);
        $return->success=empty($error);
        $return->document->id=$doc->id;
        $return->document->title=$doc->getTitle();

    } catch (Dcp\ApiUsage\Exception $e) {
        $return->success = false;
        $return->errorMessage = $e->getDcpMessage();
        $return->errorCode = $e->getDcpCode();
    } catch (\Dcp\DocManager\Exception $e) {
        $return->success = false;
        $return->errorMessage = $e->getDcpMessage();
        $return->errorCode = $e->getDcpCode();
    } catch (\Dcp\Exception $e) {
        $return->success = false;
        $return->errorMessage = $e->getDcpMessage();
        $return->errorCode = $e->getDcpCode();

    } catch (Exception $e) {
        $return->success = false;
        $return->errorMessage = $e->getMessage();
    }

    $action->lay->template = json_encode($return);
    $action->lay->noparse = true;
    header('Content-type: application/json');


}

