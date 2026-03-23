<?php

declare(strict_types=1);

namespace App\Model\Table;

class OpatrovatelkaTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_opatrovatelky';
    public const string TABLE_PREFIX = 'opatrovatelka_mapper';

    public const string COL_ID = 'id';
    public const string COL_CLIENT_NUMBER = 'client_number';
    public const string COL_NAME = 'name';
    public const string COL_SURNAME = 'surname';
    public const string COL_AGE = 'age';
    public const string COL_POHLAVIE = 'pohlavie';
    public const string COL_COUNTRY = 'country';
    public const string COL_ACTIVE = 'active';
    public const string COL_STATUS = 'status';
    public const string COL_BIRTHDAY = 'birthday';
    public const string COL_IMAGE = 'image';
    public const string COL_SMOKER = 'smoker';
    public const string COL_HEIGHT = 'height';
    public const string COL_WEIGHT = 'weight';
    public const string COL_PHONE = 'phone';
    public const string COL_PHONE2 = 'phone2';
    public const string COL_EMAIL = 'email';
    public const string COL_DRIVING_LICENCE = 'driving_licence';
    public const string COL_CITY = 'city';
    public const string COL_STREET = 'street';
    public const string COL_POSTAL_CODE = 'postal_code';
    public const string COL_WORKING_STATUS = 'working_status';
    public const string COL_AGENCY_ID = 'agency_id';
    public const string COL_CONTACT_PERSON_NAME = 'contact_person_name';
    public const string COL_CONTACT_PERSON_PHONE = 'contact_person_phone';
    public const string COL_REQUIREMENTS = 'requirements';
    public const string COL_NOTICE = 'notice';
    public const string COL_BLACKLIST = 'blacklist';
    public const string COL_FIRST_CONTACT_USER_ID = 'first_contact_user_id';
    public const string COL_ABOUT = 'about';
    public const string COL_ALLERGY = 'allergy';
    public const string COL_ALLERGY_DETAIL = 'allergy_detail';
    public const string COL_EDUCATION = 'education';
    public const string COL_COURSE = 'course';
    public const string COL_COURSE_DETAIL = 'course_detail';
    public const string COL_READY_DRIVE = 'ready_drive';
    public const string COL_HOW_LONG_WORK = 'how_long_work';
    public const string COL_HOW_LONG_WORK_GERMAN = 'how_long_work_german';
    public const string COL_LANGUAGE_SKILLS = 'language_skills';
    public const string COL_LANGUAGE_SKILLS_OTHER = 'language_skills_other';
    public const string COL_WORKING_AREA = 'working_area';
    public const string COL_DAILY_CARE = 'daily_care';
    public const string COL_HOURLY_CARE = 'hourly_care';
    public const string COL_TIME_SCALE = 'time_scale';
    public const string COL_WORK_PLACE = 'work_place';
    public const string COL_WORK_DESCRIPTION = 'work_description';
    public const string COL_GENERAL_ACTIVITIES = 'general_activities';
    public const string COL_RATING_AGENCY = 'rating_agency';
    public const string COL_PROFIL_SHOW_CONTACT = 'profil_show_contact';
    public const string COL_TYPE = 'type';
    public const string COL_JOB_POSITION_INTEREST = 'job_position_interest';
    public const string COL_WORK_SHOES = 'work_shoes';
    public const string COL_SHOE_SIZE = 'shoe_size';
    public const string COL_GERMAN_TAX_ID = 'german_tax_id';
    public const string COL_ACCOMMODATION_TYPE = 'accommodation_type';
}
