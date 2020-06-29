<?php


namespace App\Controller;


use App\Entity\Adverts;
use App\Entity\Agency;
use App\Entity\City;
use App\Entity\Members;
use App\Entity\Province;
use App\Entity\Quartier;
use App\Entity\Shop;
use App\Entity\SubCategory;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class AjaxController
 * @package App\Controller\Admin
 * @Route(path="/ajax")
 */
class AjaxController extends AbstractController
{
    const EXTENSIONS = ['jpg', 'png', 'jpeg'];
    private $entityManager;
    private $encoder;
    private $trans;
    private $mailer;
    private $tokenGenerator;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $encoder, TranslatorInterface $translator, MailerInterface $mailer, TokenGeneratorInterface $tokenGenerator, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->encoder = $encoder;
        $this->trans = $translator;
        $this->mailer = $mailer;
        $this->tokenGenerator = $tokenGenerator;
        $this->logger = $logger;
    }


    /**
     * generate password
     * @Route(path="/generate-password", name="ajax_security_generate_password", methods={"POST"})
     */
    public function generatePassword(Request $request)
    {
        $data = $this->_checkData();
        $email = isset($data['email']) ? trim($data['email']) : null;

        /** @var Members $members
         */
        $members = $this->entityManager->getRepository(Members::class)->loadMemberByUsername($email);
        if (!$members)
        {
            $response["code"] = -1;
            $response["response"] = ["message"=> $this->trans->trans('front.member.notfound')];
            return new Response(json_encode($response));
        }

        if (!$members->getEnabled()){
            $response["code"] = -1;
            $response["response"] = ["message"=> $this->trans->trans('front.member.not.confirm')];
            return new Response(json_encode($response));
        }

        $token = $this->tokenGenerator->generateToken();
        try{
            $members->setResetToken($token);
            $this->entityManager->flush();
        }catch (\Exception $e){
            $this->logger->error('generate password token: '.json_encode($e->getMessage()));
            $response["code"] = -2;
            $response["response"] = ["message" => $this->trans->trans('front.error.505')];
            return new Response(json_encode($response));
        }

        $url = $this->generateUrl('front_security_password_reset', array('token' => $token), UrlGeneratorInterface::ABSOLUTE_URL);

        exec("php ../bin/console app:send-front-email --subject=resetPassword --email=".$members->getEmail()." --url=$url --lang=".$request->getLocale()." >> ../var/log/email.log&");
        $response["code"] = 0;
        $response["response"] = ["message"=> $this->trans->trans('front.password.reset.email')];
        return new Response(json_encode($response));

    }
    /**
     * reset password
     * @Route(path="/reset-password", name="ajax_security_password_reset", methods={"POST"})
     */
    public function resetPassword()
    {
        $data = $this->_checkData();
        $password_first = isset($data['password_first']) ? trim($data['password_first']) : null;
        $password_second = isset($data['password_second']) ? trim($data['password_second']) : null;
        $token = isset($data['token']) ? trim($data['token']) : null;



        /** @var Members $member */
        $member = $this->entityManager->getRepository(Members::class)->loadMemberByToken($token);
        if (!$member)
        {
            $response["code"] = -1;
            $response["response"] = ["message"=> $this->trans->trans('front.member.notfound')];
            return new Response(json_encode($response));
        }

        if (trim($password_first) != trim($password_second)){
            $response["code"] = -1;
            $response["response"] = ["message"=> $this->trans->trans('front.password.not_match')];
            return new Response(json_encode($response));
        }
        //var_dump($password_first);die();
        try{
            $member->setPassword($this->encoder->encodePassword($member, $password_first));
            $member->setResetToken(null);
            $this->entityManager->flush();
        }catch (\Exception $e){
            $this->logger->error('reset password: '.json_encode($e->getMessage()));
            $response["code"] = -2;
            $response["response"] = ["message" => $this->trans->trans('front.error.505')];
            return new Response(json_encode($response));
        }

        $response["code"] = 0;
        $response["response"] = ["message"=> $this->trans->trans('front.password.reset.success')];
        return new Response(json_encode($response));
    }

    /**
     * update profile
     * @Route(path="/update-profile", name="ajax_security_update_profile", methods={"POST"})
     */
    public function updateProfile(Request $request)
    {
        $data = $this->_checkData();
        $firstName = isset($data['firstName']) ? trim($data['firstName']) : null;
        $lastName = isset($data['lastName']) ? trim($data['lastName']) : null;
        $userName = isset($data['userName']) ? trim($data['userName']) : null;
        $tel = isset($data['tel']) ? trim($data['tel']) : null;
        $birthDay = isset($data['birthDay']) ? trim($data['birthDay']) : null;
        $email = isset($data['email']) ? trim($data['email']) : null;
        $password_1 = isset($data['password_1']) ? trim($data['password_1']) : null;
        $password_2 = isset($data['password_2']) ? trim($data['password_2']) : null;
        $changePassword = false;
        $changeEmail = false;
        $token = '';
        /** @var Members $member */
        $member = $this->getUser();

        if (!$member)
        {
            $response["code"] = -1;
            $response["response"] = ["message"=> $this->trans->trans('front.member.notfound')];
            return new Response(json_encode($response));
        }

        if (!empty($password_1) && !empty($password_2)){
            if (!$member->isValidPassword($password_1)){
                $response["code"] = -1;
                $response["response"] = ["message"=> $this->trans->trans('front.password.format')];
                return new Response(json_encode($response));
            }
            if ($password_1 != $password_2){
                $response["code"] = -1;
                $response["response"] = ["message"=> $this->trans->trans('front.password.not_match')];
                return new Response(json_encode($response));
            }
            $changePassword = true;
        }
        if (!$member->isValidTel($tel)){
            $response["code"] = -1;
            $response["response"] = ["message"=> $this->trans->trans('front.mobileNumber.format')];
            return new Response(json_encode($response));
        }

        if ($member->getEmail() != $email){
            $changeEmail = true;
            $checkEmail = $this->entityManager->getRepository(Members::class)->findBy(['email' => $email]);
            if ($checkEmail){
                $response["code"] = -1;
                $response["response"] = ["message" => $this->trans->trans('front.email.exist')];
                return new Response(json_encode($response));
            }
        }
        if ($member->getUserName() != $userName){
            $checkUserName = $this->entityManager->getRepository(Members::class)->findBy(['userName' => $userName]);
            if ($checkUserName){
                $response["code"] = -1;
                $response["response"] = ["message" => $this->trans->trans('front.username.exist')];
                return new Response(json_encode($response));
            }
        }

        try{
            $member->setFirstName($firstName);
            $member->setLastName($lastName);
            $member->setMobileNumber($tel);
            $member->setBirthDay(new \DateTime($birthDay));
            $member->setUsername($userName);
            if ($changePassword){
                $member->setPassword($this->encoder->encodePassword($member, $password_1));
            }
            if ($changeEmail){
                $member->setEmail($email);
                $token = $this->tokenGenerator->generateToken();
                $member->setConfirmationToken($token);
                $member->setEnabled(false);
            }

            $this->entityManager->flush();

        }catch (\Exception $e){
            $this->logger->error('update profile: '.json_encode($e->getMessage()));
            $response["code"] = -2;
            $response["response"] = ["message" => $this->trans->trans('front.error.505')];
            return new Response(json_encode($response));
        }

        if ($changeEmail){
            $url = $this->generateUrl('front_security_confirm_account', array('token' => $token), UrlGeneratorInterface::ABSOLUTE_URL);
            exec("php ../bin/console app:send-front-email --subject=update-email --email=".$email." --url=$url --lang=".$request->getLocale()." >> ../var/log/email.log&");
            $this->get('security.context')->setToken(null);
            $this->get('request')->getSession()->invalidate();
        }

        $response["code"] = 0;
        $response["response"] = ["message"=> $this->trans->trans('front.profile.update.success')];
        return new Response(json_encode($response));
    }

    /**
     * @Route("/province-region", name="ajax_get_province_of_region", methods={"POST"})
     */
    public function getCProvinceOfRegion()
    {
        $data = $this->_checkData();

        $region = isset($data['region']) ? $data['region'] : null;

        $result = [];
        $provinces = $this->entityManager->getRepository(Province::class)->findBy(['region' => $region, 'enabled' => true], ['name' => 'asc']);
        foreach ($provinces as $province){
            $p['id'] = $province->getId();
            $p['code'] = $this->trans->trans($province->getCode());
            $p['name'] = $province->getCode();
            $result[] = $p;
        }
        $response["code"] = 0;
        $response["response"] = ["provinces"=> $result];
        return new Response(json_encode($response));
    }

    /**
     * @Route("/quartier-city", name="ajax_get_quartier_of_city", methods={"POST"})
     */
    public function getQuartierOfCity()
    {
        $data = $this->_checkData();

        $city = isset($data['city']) ? $data['city'] : null;

        $result = [];
        $quartiers = $this->entityManager->getRepository(Quartier::class)->findBy(['city' => $city, 'enabled' => true], ['name' => 'ASC']);
        foreach ($quartiers as $quartier){
            $p['id'] = $quartier->getId();
            $p['code'] = $this->trans->trans($quartier->getCode());
            $p['name'] = $quartier->getCode();
            $result[] = $p;
        }
        $response["code"] = 0;
        $response["response"] = ["quartiers"=> $result];
        return new Response(json_encode($response));
    }

    /**
     * @Route("/city-province", name="ajax_get_city_of_province", methods={"POST"})
     */
    public function getCityOfProvince()
    {
        $data = $this->_checkData();

        $province = isset($data['province']) ? $data['province'] : null;

        $result = [];
        $cities = $this->entityManager->getRepository(City::class)->findBy(['province' => $province, 'enabled' => true], ['name'=> 'ASC']);
        foreach ($cities as $city){
            $p['id'] = $city->getId();
            $p['code'] = $this->trans->trans($city->getCode());
            $p['name'] = $city->getCode();
            $result[] = $p;
        }

        $response["code"] = 0;
        $response["response"] = ["cities"=> $result];
        return new Response(json_encode($response));
    }

    /**
     * @Route("/subcategory/category/", name="ajax_get_subcategory_by_category", methods={"POST"})
     */
    public function getSubCategoryOfCategory()
    {
        $data = $this->_checkData();

        $category = isset($data['category']) ? $data['category'] : null;

        $result = [];
        $subcategories = $this->entityManager->getRepository(SubCategory::class)->findBy(['category' => $category, 'enabled' => true], ['name'=> 'ASC']);
        foreach ($subcategories as $subcategory){
            $p['id'] = $subcategory->getId();
            $p['code'] = $this->trans->trans($subcategory->getCode());
            $p['name'] = $subcategory->getCode();
            $result[] = $p;
        }

        $response["code"] = 0;
        $response["response"] = ["subcategories"=> $result];
        return new Response(json_encode($response));
    }



    /**
     * contact agent
     * @Route(path="/contact-agent", name="ajax_contact_agent", methods={"POST"})
     */
    public function contactAgent(Request $request)
    {
        $data = $this->_checkData();
        $advertId = isset($data['advertId']) ? trim($data['advertId']) : null;
        $email = isset($data['email']) ? trim($data['email']) : null;
        $fullName = isset($data['fullName']) ? str_replace(' ', '_', trim($data['fullName'])) : null;
        $message = isset($data['message']) ? str_replace(' ', '_', trim($data['message'])) : null;
        $tel = isset($data['tel']) ? trim($data['tel']) : null;

        /** @var Adverts $advert */
        $advert = $this->entityManager->getRepository(Adverts::class)->find(intval($advertId));
        if (!$advert){
            $response["code"] = -1;
            $response["response"] = ["message"=> $this->trans->trans('front.adverts.not-available')];
            return new Response(json_encode($response));
        }

        $member = $advert->getMember();

        exec("php ../bin/console app:send-front-email --subject=adverts --email=".$member->getEmail()." --emailContact=$email --tel=$tel --fullName=$fullName --message=$message --lang=".$request->getLocale()." >> ../var/log/advertEmails.log&");

        $response["code"] = 0;
        $response["response"] = ["message"=> $this->trans->trans('front.adverts.contact.success')];
        return new Response(json_encode($response));

    }

    /**
     * contact agent
     * @Route(path="/search-city-quartier", name="ajax_search_city_quartier", methods={"POST"})
     */
    public function searchCityQuartier(Request $request)
    {
        $data = $this->_checkData();
        $citySearch = isset($data['city']) ? trim($data['city']) : null;

        $result = [];

        $provinces = $this->entityManager->getRepository(Province::class)->findByProvinceName($citySearch);
        if (count($provinces) > 0){
            /** @var Province $province */
            foreach ($provinces as $province){
                $p['id'] = $province->getId();
                $p['code'] = $this->trans->trans($province->getCode());
                $p['name'] = $province->getName();
                $p['obj'] = 'province';
                $result[] = $p;
            }
        }

        $cities = $this->entityManager->getRepository(City::class)->findByCityName($citySearch);
        if (count($cities) > 0){
            /** @var City $city */
            foreach ($cities as $city){
                $p['id'] = $city->getId();
                $p['code'] = $this->trans->trans($city->getProvince()->getCode()). ', '.$this->trans->trans($city->getCode());
                $p['name'] = $city->getName();
                $p['obj'] = 'city';
                $result[] = $p;
            }
        }

        $quartiers = $this->entityManager->getRepository(Quartier::class)->findByQuartierName($citySearch);
        if (count($quartiers) > 0){
            /** @var Quartier $quartier **/
            foreach ($quartiers as $quartier){
                $p['id'] = $quartier->getId();
                $p['code'] = $this->trans->trans($quartier->getCity()->getProvince()->getCode()) .', '.$this->trans->trans($quartier->getCity()->getCode()).', '.$this->trans->trans($quartier->getCode());
                $p['name'] = $quartier->getName();
                $p['obj'] = 'quartier';
                $result[] = $p;
            }
        }

        $tempArr = array_unique(array_column($result, 'code'));
        $result1 = array_slice(array_intersect_key($result, $tempArr), 0, 8);


        $response["code"] = 0;
        $response["response"] = ["cities"=> $result1];
        return new Response(json_encode($response));
    }

    /**
     * contact agent
     * @Route(path="/contact-agency", name="ajax_contact_agency", methods={"POST"})
     */
    public function contactAgency(Request $request)
    {
        $data = $this->_checkData();
        $agencyId = isset($data['agencyId']) ? trim($data['agencyId']) : null;
        $email = isset($data['email']) ? trim($data['email']) : null;
        $fullName = isset($data['fullName']) ? str_replace(' ', '_', trim($data['fullName'])) : null;
        $message = isset($data['message']) ? str_replace(' ', '_', trim($data['message'])) : null;
        $tel = isset($data['tel']) ? trim($data['tel']) : null;

        /** @var Agency $agency */
        $agency = $this->entityManager->getRepository(Agency::class)->find(intval($agencyId));
        if (!$agency){
            $response["code"] = -1;
            $response["response"] = ["message"=> $this->trans->trans('front.agency.not-available')];
            return new Response(json_encode($response));
        }

        exec("php ../bin/console app:send-front-email --subject=agency --email=".$agency->getEmail()." --emailContact=$email --tel=$tel --fullName=$fullName --message=$message --lang=".$request->getLocale()." >> ../var/log/advertEmails.log&");

        $response["code"] = 0;
        $response["response"] = ["message"=> $this->trans->trans('front.agency.contact.success')];
        return new Response(json_encode($response));

    }


    /**
     * contact agent
     * @Route(path="/request-info", name="ajax_request_info", methods={"POST"})
     */
    public function requestInfo(Request $request)
    {
        $data = $this->_checkData();
        $advertId = isset($data['advertId']) ? trim($data['advertId']) : null;
        $email = isset($data['email']) ? trim($data['email']) : null;
        $fullName = isset($data['name']) ? str_replace(' ', '_', trim($data['name'])) : null;
        $message = isset($data['message']) ? str_replace(' ', '_', trim($data['message'])) : null;
        $subject = isset($data['subject']) ? trim($data['subject']) : null;

        /** @var Adverts $advert */
        $advert = $this->entityManager->getRepository(Adverts::class)->find(intval($advertId));
        if (!$advert){
            $response["code"] = -1;
            $response["response"] = ["message"=> $this->trans->trans('front.adverts.not-available')];
            return new Response(json_encode($response));
        }

        $member = $advert->getMember();

        exec("php ../bin/console app:send-front-email --subject=request --email=".$member->getEmail()." --emailContact=$email --reason=$subject --fullName=$fullName --message=$message --lang=".$request->getLocale()." >> ../var/log/advertEmails.log&");

        $response["code"] = 0;
        $response["response"] = ["message"=> $this->trans->trans('front.adverts.contact.success')];
        return new Response(json_encode($response));

    }


    /**
     * contact US
     * @Route(path="/contact_us", name="ajax_contact_us", methods={"POST"})
     */
    public function contactUs(Request $request)
    {
        $data = $this->_checkData();
        $email = isset($data['email']) ? trim($data['email']) : null;
        $fullName = isset($data['fullName']) ? str_replace(' ', '_', trim($data['fullName'])) : null;
        $message = isset($data['message']) ? str_replace(' ', '_', trim($data['message'])) : null;

        exec("php ../bin/console app:send-front-email --subject=contactus  --emailContact=$email --fullName=$fullName --message=$message --lang=".$request->getLocale()." >> ../var/log/advertEmails.log&");

        $response["code"] = 0;
        $response["response"] = ["message"=> $this->trans->trans('front.adverts.contact.success')];
        return new Response(json_encode($response));

    }

    /**
     * @param Request $request
     * @Route(path="uploadPicture", name="ajax_upload_member_picture", methods={"POST"})
     */
    public function UploadMemberPicture(Request $request){


        if (isset($_FILES["file"]["name"])){
            /** @var Members $member */
            $member = $this->getUser();

           try{
               $uploadsDir = $this->getParameter('uploads_directory_members');

               $fileName = basename($_FILES['file']['name']);
               $targetFilePath = $uploadsDir . '/' .$fileName;
               $explodeFileName = explode('.', $fileName);
               $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
               if (count($explodeFileName) > 2){
                   throw new \Exception('file.invalid');
               }

               if (!in_array($fileType, self::EXTENSIONS)){
                   throw new \Exception('file.extension.invalid');
               }
               $ImageName = time().rand(1000, 9999).'.png';
               $targetFilePath = $uploadsDir . '/' .$ImageName;
               move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath);

               $member->setImage($ImageName);

               $this->entityManager->flush();

               $response["code"] = 0;
               $response["response"] = ["message"=> $this->trans->trans('front.image.upload.success'), "imageSrc" =>$ImageName ];
               return new Response(json_encode($response));
           }catch (\Exception $e){
               $response["code"] = -1;
               $response["response"] = ["message"=> $this->trans->trans($e->getMessage())];
               return new Response(json_encode($response));
           }
        }

    }

    /**
     * @param $request
     * @param $id
     * @return Response
     * @Route(path="/delete_image/{id}", name="ajax_delete_img")
     */
    public function deletePicture(Request $request, $id){

        $imageToDelete = $request->get('key');

        $advert = $this->entityManager->getRepository(Shop::class)->find(intval($id));
        if ($advert){
            $uploadsDir = $this->getParameter('uploads_directory');
            $newDirName = $uploadsDir.$advert->getImages();

            if (is_file($newDirName.'/'.$imageToDelete)){
                //unlink($newDirName.'/'.$imageToDelete);
            }
        }

        return new Response(true);
    }

    /**
     *  @Route(path="/add_wish", name="ajax_add_wish", methods={"POST"})
     */
    public function addWish(Request $request){
        $data = $this->_checkData();
        /** @var Members $member */
        $member = $this->getUser();

        $wishList = json_decode($member->getWishList(), true)?: [];
        $advertId = isset($data['advertId']) ? trim($data['advertId']) : null;

        array_push($wishList, intval($advertId));

        $member->setWishList(json_encode($wishList));
        $member->setWishNumber($member->getWishNumber() + 1);
        $this->entityManager->flush();

        $response["code"] = 0;
        $response["response"] = ["message"=> 'success'];
        return new Response(json_encode($response));
    }

    /**
     *  @Route(path="/remove_wish", name="ajax_remove_wish", methods={"POST"})
     */
    public function removeWish(Request $request){
        $data = $this->_checkData();
        /** @var Members $member */
        $member = $this->getUser();

        $wishList = json_decode($member->getWishList(), true);
        $advertId = isset($data['advertId']) ? trim($data['advertId']) : null;

        $key = array_search($advertId, $wishList);
        if ($key !== false) {
            unset($wishList[$key]);
        }

        $member->setWishList(json_encode($wishList));
        //$member->setWishNumber($member->getWishNumber() - 1);
        $this->entityManager->flush();

        $response["code"] = 0;
        $response["response"] = ["message"=> 'success'];
        return new Response(json_encode($response));
    }
    private function _checkData()
    {
        $data = json_decode(file_get_contents('php://input'), TRUE);
        if ($data == null)
        {
            $response["code"] = -1;
            $response["response"] = ["message"=> $this->trans->trans('admin.form.empty')];
            return new Response(json_encode($response));
        }
        return $data;
    }
}
