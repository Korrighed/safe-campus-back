<?php

namespace Database\Seeders;

use App\Enums\Province;
use App\Enums\TelephoneType;
use App\Models\Contact;
use App\Models\SousTheme;
use App\Models\Telephone;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Contacts en dur. Base : ancien database/seeders/data/contact.csv (38
 * contacts territoriaux), deja converti vers la taxonomie 2026-07-31 (3
 * themes x 3 sous-themes) — ecarts et decisions dans
 * database/seeders/data/NOTES.md (non retrouve au moment de l'ecriture).
 * 4 contacts campus UNC ajoutes depuis l'ancien app/data/themes.js du
 * front (supprime au commit 9113ec0). Idempotent via `ref` (updateOrCreate).
 */
class ContactSeeder extends Seeder
{
    /**
     * @var list<array{
     *     ref: string,
     *     nom: string,
     *     mail: ?string,
     *     localisation: ?string,
     *     province: ?Province,
     *     site_web: ?string,
     *     telephones: list<array{numero: string, type: TelephoneType, numero_vert?: bool}>,
     *     sous_themes: list<string>,
     *     horaires?: ?string,
     *     remarques?: ?string,
     *     gratuit?: ?bool,
     *     latitude?: ?float,
     *     longitude?: ?float,
     *     position_territoire?: bool,
     *     actif?: bool
     * }>
     */
    private const CONTACTS = [
        [
            'ref' => 'samu',
            'nom' => 'SAMU',
            'mail' => null,
            'localisation' => 'Tout le territoire NC',
            'province' => null,
            'position_territoire' => true,
            'site_web' => null,
            'telephones' => [
                ['numero' => '15', 'type' => TelephoneType::Urgence],
            ],
            'sous_themes' => ['violences_sexuelles', 'harcelement'],
        ],
        [
            'ref' => 'police',
            'nom' => 'Police / Gendarmerie',
            'mail' => null,
            'localisation' => 'Tout le territoire NC',
            'province' => null,
            'position_territoire' => true,
            'site_web' => 'gendarmerie.interieur.gouv.fr',
            'telephones' => [
                ['numero' => '17', 'type' => TelephoneType::Urgence],
            ],
            'sous_themes' => ['violences_sexuelles', 'harcelement'],
        ],
        [
            'ref' => 'pompiers',
            'nom' => 'Sapeurs-Pompiers',
            'mail' => null,
            'localisation' => 'Tout le territoire NC',
            'province' => null,
            'position_territoire' => true,
            'site_web' => 'securite-civile.gouv.nc',
            'telephones' => [
                ['numero' => '18', 'type' => TelephoneType::Urgence],
            ],
            'sous_themes' => ['violences_sexuelles', 'harcelement'],
        ],
        [
            'ref' => 'prevention_suicide_3114',
            'nom' => 'Prevention du suicide (3114)',
            'mail' => null,
            'localisation' => 'Ligne nationale accessible depuis la NC',
            'province' => null,
            'site_web' => '3114.fr',
            'telephones' => [
                ['numero' => '3114', 'type' => TelephoneType::Urgence],
            ],
            'sous_themes' => ['depression', 'anxiete', 'burn_out'],
        ],
        [
            'ref' => 'sms_signalement_violence',
            'nom' => 'SMS signalement violence (DTAV)',
            'mail' => null,
            'localisation' => 'Tout le territoire NC',
            'province' => null,
            'position_territoire' => true,
            'site_web' => null,
            'telephones' => [
                ['numero' => '500 067', 'type' => TelephoneType::Sms],
            ],
            'sous_themes' => ['harcelement', 'violences_sexuelles'],
            'remarques' => 'Service porte par la DTAV. Preciser un nom et un numero pour etre rappele.',
        ],
        [
            'ref' => 'sos_ecoute',
            'nom' => 'SOS Ecoute - Point Ecoute (ACSMS)',
            'mail' => 'point-ecoute@hotmail.com',
            'localisation' => 'Service telephonique - tout le territoire NC',
            'province' => null,
            'position_territoire' => true,
            'site_web' => 'acsms.nc/lepointsosecoute',
            'telephones' => [
                ['numero' => '05 30 30', 'type' => TelephoneType::Fixe, 'numero_vert' => true],
            ],
            'sous_themes' => ['anxiete', 'depression', 'burn_out', 'alcool', 'drogue', 'violences_sexuelles', 'violences_sexistes', 'harcelement'],
            'horaires' => 'Lun-sam 9h-13h, dim 9h-13h et 17h-19h (tous les jours, week-end et feries compris)',
        ],
        [
            'ref' => 'adavi_siege',
            'nom' => 'ADAVI - Aide aux victimes (siege)',
            'mail' => 'accueil@adavi.nc',
            'localisation' => '33 avenue Henri Lafleur, 98800 Noumea',
            'province' => Province::Sud,
            'site_web' => 'violences-conjugales.gouv.nc/organismes/association-pour-lacces-au-droit-et-laide-aux-victimes-adavi',
            'telephones' => [
                ['numero' => '27 76 08', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['violences_sexuelles', 'harcelement'],
            'latitude' => -22.2740442,
            'longitude' => 166.4451268,
        ],
        [
            'ref' => 'mcpf_noumea',
            'nom' => 'MCPF - Gendarmerie NC (Noumea)',
            'mail' => 'mpf.comgendnc@gendarmerie.interieur.gouv.fr',
            'localisation' => 'Noumea',
            'province' => Province::Sud,
            'site_web' => 'gendarmerie.interieur.gouv.fr',
            'telephones' => [
                ['numero' => '29 56 91', 'type' => TelephoneType::Fixe],
                ['numero' => '78 49 97', 'type' => TelephoneType::Mobile],
            ],
            'sous_themes' => ['harcelement', 'violences_sexuelles'],
        ],
        [
            'ref' => 'mcpf_kone',
            'nom' => 'MCPF - Gendarmerie NC (Kone)',
            'mail' => 'mpf.comgendnc@gendarmerie.interieur.gouv.fr',
            'localisation' => 'Kone',
            'province' => Province::Nord,
            'site_web' => 'gendarmerie.interieur.gouv.fr',
            'telephones' => [
                ['numero' => '45 12 19', 'type' => TelephoneType::Fixe],
                ['numero' => '77 45 98', 'type' => TelephoneType::Mobile],
            ],
            'sous_themes' => ['harcelement', 'violences_sexuelles'],
        ],
        [
            'ref' => 'declic',
            'nom' => 'DECLIC - ASS-NC (jeunes -25 ans)',
            'mail' => 'declic@ass.nc',
            'localisation' => '16 rue Gallieni, 98851 Noumea',
            'province' => Province::Sud,
            'site_web' => 'santepourtous.nc/les-thematiques/addictions/trouver-de-l-aide/declic',
            'telephones' => [
                ['numero' => '25 50 78', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['alcool', 'drogue', 'tabac'],
            'latitude' => -22.269840994209325,
            'longitude' => 166.43871348217928,
        ],
        [
            'ref' => 'croix_rouge_aller_vers',
            'nom' => 'Croix-Rouge NC - Aller vers',
            'mail' => 'allervers.dt988@croix-rouge.fr',
            'localisation' => '23 rue du Dr Collard, 98845 Noumea',
            'province' => Province::Sud,
            'site_web' => 'croix-rouge.nc',
            'telephones' => [
                ['numero' => '96 35 95', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['violences_sexuelles', 'harcelement', 'anxiete', 'depression', 'burn_out'],
            'latitude' => -22.2504825,
            'longitude' => 166.4519872,
        ],
        [
            'ref' => 'cmp_gallieni',
            'nom' => 'CMP Gallieni - CHS Albert Bousquet (adultes)',
            'mail' => 'sec.gallieni@chs.nc',
            'localisation' => '1 rue du general Gallieni, 98845 Noumea',
            'province' => Province::Sud,
            'site_web' => 'chs.nc',
            'telephones' => [
                ['numero' => '27 52 56', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['anxiete', 'depression', 'burn_out'],
            'latitude' => -22.2684389,
            'longitude' => 166.4387889,
        ],
        [
            'ref' => 'aup_gaston_bourret',
            'nom' => 'Accueil Urgences Psychiatriques - CHT Gaston-Bourret',
            'mail' => null,
            'localisation' => '110 bd Joseph Wamytan, 98835 Dumbea (Medipole de Koutio)',
            'province' => Province::Sud,
            'site_web' => 'cht.nc',
            'telephones' => [
                ['numero' => '20 80 00', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['depression', 'anxiete', 'burn_out'],
            'latitude' => -22.2100017,
            'longitude' => 166.4533908,
        ],
        [
            'ref' => 'csapa',
            'nom' => 'CSAPA - Addictologie (+25 ans)',
            'mail' => 'secretariat.csa@chs.nc',
            'localisation' => '83 bd Joseph Wamytan, 98800 Noumea',
            'province' => Province::Sud,
            'site_web' => 'drogues-info-service.fr/Adresses-utiles/100373',
            'telephones' => [
                ['numero' => '24 01 66', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['alcool', 'drogue', 'tabac'],
        ],
        [
            'ref' => 'psdv',
            'nom' => 'Prenez Soin De Vous (PSDV) - Croix-Rouge NC',
            'mail' => null,
            'localisation' => '28 rue du Commandant Riviere, 98800 Noumea',
            'province' => Province::Sud,
            'site_web' => 'croix-rouge.nc',
            'telephones' => [
                ['numero' => '27 92 98', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['alcool', 'drogue'],
            'latitude' => -22.285741,
            'longitude' => 166.4522631,
        ],
        [
            'ref' => 'dav',
            'nom' => "DAV - Dispositif d'Accueil des Victimes (Medipole)",
            'mail' => 'dav@cht.nc',
            'localisation' => '110 bd Joseph Wamytan, 98835 Dumbea-sur-Mer',
            'province' => Province::Sud,
            'site_web' => 'cht.nc',
            'telephones' => [
                ['numero' => '20 83 40', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['violences_sexuelles', 'harcelement'],
        ],
        [
            'ref' => 'sos_violences',
            'nom' => 'SOS Victimes',
            'mail' => 'contact@sosvictimesnc.nc',
            'localisation' => '53 rue Georges Clemenceau - BP 2629, 98846 Noumea Cedex',
            'province' => Province::Sud,
            'site_web' => 'violences-conjugales.gouv.nc/organismes/sos-victimes',
            'telephones' => [
                ['numero' => '05 11 11', 'type' => TelephoneType::Fixe, 'numero_vert' => true],
                ['numero' => '25 00 04', 'type' => TelephoneType::Fixe, 'numero_vert' => true],
            ],
            'sous_themes' => ['violences_sexistes', 'violences_sexuelles'],
            'horaires' => 'Lun-jeu 8h-11h30 et 13h-16h, ven 13h-15h',
            'remarques' => 'Accompagnement judiciaire et presence aux audiences, administrateur ad hoc. Peut etre contacte a tout moment, avant ou apres depot de plainte.',
        ],
        [
            'ref' => 'sos_victimes_kone',
            'nom' => 'SOS Victimes - Permanence Kone',
            'mail' => 'contact@sosvictimesnc.nc',
            'localisation' => 'WVR8+3C3, Kone',
            'province' => Province::Nord,
            'site_web' => 'violences-conjugales.gouv.nc/organismes/sos-victimes',
            'telephones' => [
                ['numero' => '05 11 11', 'type' => TelephoneType::Fixe, 'numero_vert' => true],
                ['numero' => '25 00 04', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['violences_sexistes', 'violences_sexuelles'],
        ],
        [
            'ref' => 'le_relais',
            'nom' => 'Relais violences conjugales et intrafamiliales - Province Sud',
            'mail' => null,
            'localisation' => '12 avenue Paul Doumer, 98800 Noumea',
            'province' => Province::Sud,
            'site_web' => 'province-sud.nc/sante-social-egalite/egalite-genres-droits-intrafamiliaux/relais-violences-conjugales-et-intrafamiliales',
            'telephones' => [
                ['numero' => '23 26 26', 'type' => TelephoneType::Fixe],
                ['numero' => '20 37 70', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['violences_sexistes', 'violences_sexuelles', 'harcelement'],
            'horaires' => 'Lun-jeu 7h30-16h en continu (avec ou sans rendez-vous), ven sur rendez-vous',
            'latitude' => -22.2681309,
            'longitude' => 166.44007,
        ],
        [
            'ref' => 'cidfe',
            'nom' => 'CIDFE - Droits des Femmes et Egalite',
            'mail' => 'cidfe@province-sud.nc',
            'localisation' => '6 route des Artifices, 98849 Noumea',
            'province' => Province::Sud,
            'site_web' => 'province-sud.nc/votre-province/administration/cidfe',
            'telephones' => [
                ['numero' => '20 37 40', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['harcelement', 'violences_sexuelles'],
        ],
        [
            'ref' => 'commissariat_noumea_psy',
            'nom' => 'Commissariat Noumea - Psychologue',
            'mail' => 'dtpn988-stsp-psychologue@interieur.gouv.fr',
            'localisation' => '36 rue de Sebastopol, 98800 Noumea',
            'province' => Province::Sud,
            'site_web' => null,
            'telephones' => [
                ['numero' => '24 33 52', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['violences_sexuelles', 'harcelement'],
        ],
        [
            'ref' => 'commissariat_noumea_social',
            'nom' => 'Commissariat Noumea - Intervenant social',
            'mail' => 'laurence.grangeon-988@interieur.gouv.fr',
            'localisation' => '36 rue de Sebastopol, 98800 Noumea',
            'province' => Province::Sud,
            'site_web' => null,
            'telephones' => [
                ['numero' => '24 34 18', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['violences_sexuelles', 'harcelement'],
        ],
        [
            'ref' => 'dassps',
            'nom' => 'DASSPS - Province Nord',
            'mail' => 'dassps-sas@province-nord.nc',
            'localisation' => 'Hotel de la Province Nord, 41 av. Jimmy Welepane, 98860 Kone',
            'province' => Province::Nord,
            'site_web' => 'province-nord.nc/sante-cohesion-sociale/permanencier-social',
            'telephones' => [
                ['numero' => '47 72 30', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['anxiete', 'depression', 'burn_out', 'alcool', 'drogue', 'harcelement'],
        ],
        [
            'ref' => 'chn_koumac',
            'nom' => 'CHN - Hopital Paula Thavoavianon (Koumac)',
            'mail' => 'direction@chn.nc',
            'localisation' => 'Avenue Emile Frouin, 98850 Koumac',
            'province' => Province::Nord,
            'site_web' => 'gouv.nc/etablissement-public/centre-hospitalier-du-nord-0',
            'telephones' => [
                ['numero' => '42 65 14', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['violences_sexuelles', 'harcelement'],
        ],
        [
            'ref' => 'chn_poindimie',
            'nom' => 'CHN - Hopital Raymond Doui Nebayes (Poindimie)',
            'mail' => 'poindimie@chn.nc',
            'localisation' => 'RT3, 98822 Poindimie',
            'province' => Province::Nord,
            'site_web' => 'gouv.nc/etablissement-public/centre-hospitalier-du-nord-0',
            'telephones' => [
                ['numero' => '42 71 44', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['violences_sexuelles', 'harcelement'],
        ],
        [
            'ref' => 'chn_kone',
            'nom' => 'CHN - Pole Sanitaire du Nord (Kone)',
            'mail' => 'kone@chn.nc',
            'localisation' => '41 av. Jimmy Welepane, 98860 Kone',
            'province' => Province::Nord,
            'site_web' => 'gouv.nc/etablissement-public/centre-hospitalier-du-nord-0',
            'telephones' => [
                ['numero' => '42 10 00', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['violences_sexuelles', 'harcelement'],
        ],
        [
            'ref' => 'ass_addicto_nord',
            'nom' => 'ASS-NC - Addictologie Province Nord',
            'mail' => null,
            'localisation' => 'CHN Koumac / Kone',
            'province' => Province::Nord,
            'site_web' => 'santepourtous.nc',
            'telephones' => [
                ['numero' => '42 11 75', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['alcool', 'drogue'],
            'latitude' => -21.098274,
            'longitude' => 164.887448,
        ],
        [
            'ref' => 'adavi_kone',
            'nom' => 'ADAVI - Permanence Kone',
            'mail' => 'accueil@adavi.nc',
            'localisation' => 'Tribunal - section detachee de Kone',
            'province' => Province::Nord,
            'site_web' => 'violences-conjugales.gouv.nc/organismes/association-pour-lacces-au-droit-et-laide-aux-victimes-adavi',
            'telephones' => [
                ['numero' => '27 76 08', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['violences_sexuelles', 'harcelement'],
        ],
        [
            'ref' => 'chs_koumac',
            'nom' => 'CHS Albert Bousquet - Antenne Koumac',
            'mail' => null,
            'localisation' => 'Village RT1, 98850 Koumac',
            'province' => Province::Nord,
            'site_web' => 'chs.nc',
            'telephones' => [
                ['numero' => '42 76 56', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['anxiete', 'depression', 'burn_out'],
        ],
        [
            'ref' => 'chs_poindimie',
            'nom' => 'CHS Albert Bousquet - Antenne Poindimie',
            'mail' => 'sec.poindimie@chs.nc',
            'localisation' => 'Village RP3, 98822 Poindimie',
            'province' => Province::Nord,
            'site_web' => 'chs.nc',
            'telephones' => [
                ['numero' => '42 60 34', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['anxiete', 'depression', 'burn_out'],
        ],
        [
            'ref' => 'chs_kone_ufn',
            'nom' => 'CHS Albert Bousquet - Antenne UFN Kone',
            'mail' => null,
            'localisation' => 'Village Route Provinciale 1, Kone',
            'province' => Province::Nord,
            'site_web' => 'chs.nc',
            'telephones' => [
                ['numero' => '24 36 36', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['anxiete', 'depression', 'burn_out'],
        ],
        [
            'ref' => 'dacas',
            'nom' => 'DACAS - Province des Iles',
            'mail' => 'sec_dacas@loyalty.nc',
            'localisation' => 'BP 50 We, 98820 Lifou',
            'province' => Province::Iles,
            'site_web' => 'province-iles.nc/page/la-direction-de-laction-communautaire-et-de-laction-sanitaire-dacas',
            'telephones' => [
                ['numero' => '45 06 23', 'type' => TelephoneType::Fixe],
                ['numero' => '45 52 46', 'type' => TelephoneType::Fixe],
                ['numero' => '73 07 92', 'type' => TelephoneType::Mobile],
            ],
            'sous_themes' => ['anxiete', 'depression', 'burn_out', 'harcelement', 'alcool'],
        ],
        [
            'ref' => 'adavi_iles',
            'nom' => 'ADAVI - Permanences Lifou / Ile des Pins',
            'mail' => 'accueil@adavi.nc',
            'localisation' => 'Lifou / Ile des Pins',
            'province' => Province::Iles,
            'site_web' => 'violences-conjugales.gouv.nc/organismes/association-pour-lacces-au-droit-et-laide-aux-victimes-adavi',
            'telephones' => [
                ['numero' => '27 76 08', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['violences_sexuelles', 'harcelement'],
        ],
        [
            'ref' => 'gendarmerie_iles',
            'nom' => 'Gendarmerie - brigades locales (iles)',
            'mail' => 'mpf.comgendnc@gendarmerie.interieur.gouv.fr',
            'localisation' => 'Mare, Lifou, Ouvea, Ile des Pins',
            'province' => Province::Iles,
            'site_web' => 'gendarmerie.interieur.gouv.fr',
            'telephones' => [
                ['numero' => '17', 'type' => TelephoneType::Urgence],
            ],
            'sous_themes' => ['violences_sexuelles', 'harcelement'],
        ],
        [
            'ref' => 'portail_signalement_violences',
            'nom' => 'Portail national signalement violences',
            'mail' => null,
            'localisation' => null,
            'province' => null,
            'site_web' => 'arretonslesviolences.gouv.fr',
            'telephones' => [],
            'sous_themes' => ['violences_sexuelles', 'harcelement'],
        ],
        [
            'ref' => 'dignity',
            'nom' => 'Application Dignity',
            'mail' => null,
            'localisation' => null,
            'province' => null,
            'site_web' => 'dignity-asso.com',
            'telephones' => [
                ['numero' => '500 067', 'type' => TelephoneType::Sms],
            ],
            'sous_themes' => ['harcelement', 'violences_sexuelles'],
        ],
        [
            'ref' => 'cavad_nc',
            'nom' => 'CAVAD-NC - Cartographie aide aux victimes',
            'mail' => null,
            'localisation' => null,
            'province' => null,
            'site_web' => 'violencesconjugales.gouv.nc',
            'telephones' => [],
            'sous_themes' => ['harcelement', 'violences_sexuelles'],
        ],
        [
            'ref' => 'ass_nc',
            'nom' => 'ASS-NC - Agence Sanitaire et Sociale',
            'mail' => 'ass.nc@ass.nc',
            'localisation' => '16 rue Gallieni, 98800 Noumea',
            'province' => Province::Sud,
            'site_web' => 'santepourtous.nc',
            'telephones' => [
                ['numero' => '25 07 60', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['alcool', 'drogue', 'tabac'],
        ],
        [
            'ref' => 'cafd_pNord',
            'nom' => 'Centre d\'Accueil des Femmes en Difficulte - Province Nord',
            'mail' => null,
            'localisation' => 'Kamalac, Province Nord',
            'province' => Province::Nord,
            'site_web' => 'province-nord.nc/femme/structures-daccueil',
            'telephones' => [
                ['numero' => '42 39 74', 'type' => TelephoneType::Fixe],
                ['numero' => '71 72 96', 'type' => TelephoneType::Mobile],
                ['numero' => '05 00 35', 'type' => TelephoneType::Fixe, 'numero_vert' => true],
            ],
            'sous_themes' => ['violences_sexistes', 'violences_sexuelles'],
            'remarques' => 'Ligne juriste CAFED (numero gratuit) : 05 00 35.',
        ],
        [
            'ref' => 'dtav',
            'nom' => "DTAV - Delegation Territoriale d'Aides aux Victimes",
            'mail' => 'victime-988@interieur.gouv.fr',
            'localisation' => 'Centre administratif du Haut-Commissariat, 9 bis rue de la Republique, Noumea',
            'province' => Province::Sud,
            'site_web' => 'province-sud.nc/exposition/silences-brises/dtav',
            'telephones' => [
                ['numero' => '24 34 24', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['violences_sexistes', 'violences_sexuelles', 'harcelement'],
            'horaires' => 'Lun-ven 8h-12h15 et 12h45-17h',
            'remarques' => 'Psychologue Anne Cecile Selefen (voir contact commissariat_noumea_psy) et intervenante sociale Laurence Grangeon (voir contact commissariat_noumea_social) rattachees a la DTAV.',
        ],
        [
            'ref' => 'gendarmerie_social_sud',
            'nom' => 'Intervenante sociale - Gendarmerie Province Sud',
            'mail' => null,
            'localisation' => 'Province Sud',
            'province' => Province::Sud,
            'site_web' => 'gendarmerie.interieur.gouv.fr',
            'telephones' => [
                ['numero' => '73 06 99', 'type' => TelephoneType::Mobile],
                ['numero' => '20 78 23', 'type' => TelephoneType::Fixe],
                ['numero' => '50 63 94', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['violences_sexuelles', 'harcelement'],
        ],
        [
            'ref' => 'prismes',
            'nom' => 'P.R.I.S.M.E.S',
            'mail' => null,
            'localisation' => null,
            'province' => Province::Sud,
            'site_web' => null,
            'telephones' => [
                ['numero' => '24 15 17', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['violences_sexuelles'],
            'remarques' => 'Preservatifs gratuits, consultations gratuites de sexologie, ecoute et depistage divers, suivi de grossesse. Permanence RAINBOWLUTION (LGBTQIA+) le premier mercredi de chaque mois, 11h30-16h30, sans rendez-vous (contact via Facebook et Instagram).',
        ],
        [
            'ref' => 'espas_cmp',
            'nom' => 'ESPAS CMP',
            'mail' => null,
            'localisation' => null,
            'province' => Province::Sud,
            'site_web' => 'province-sud.nc/sante-social-egalite/services-medico-sociaux-et-consultations/espace-sante-contraception-ivg-et-depistage-des-ist/espace-sante',
            'telephones' => [
                ['numero' => '20 47 40', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['violences_sexuelles'],
            'remarques' => 'Depistage anonyme et gratuit, preservatifs (CCF, etc.).',
        ],
        // Contacts campus UNC, repris de l'ancien app/data/themes.js (front, commit 9113ec0~1).
        // Adresse verifiee : 145 Avenue James Cook, Nouville, Noumea (UNC). Emails @example.com
        // du front ecartes (placeholders) sauf egalite@unc.nc, seul domaine reel.
        [
            'ref' => 'service_sante_etudiante',
            'nom' => 'Service Sante Etudiante - UNC',
            'mail' => null,
            'localisation' => '145 Avenue James Cook, 98800 Noumea',
            'province' => Province::Sud,
            'site_web' => 'unc.nc/vie-etudiante/sante',
            'telephones' => [
                ['numero' => '26 58 00', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['anxiete', 'depression', 'burn_out'],
            'horaires' => 'Sur rendez-vous, lundi-vendredi',
            'remarques' => 'Ecoute, orientation et suivi psychologique pour les etudiants',
            'latitude' => -22.2616715,
            'longitude' => 166.4008725,
        ],
        [
            'ref' => 'medecin_universitaire',
            'nom' => 'Medecin universitaire - UNC',
            'mail' => null,
            'localisation' => '145 Avenue James Cook, 98800 Noumea',
            'province' => Province::Sud,
            'site_web' => 'unc.nc/vie-etudiante/sante',
            'telephones' => [
                ['numero' => '26 58 00', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['alcool', 'tabac', 'burn_out'],
            'horaires' => 'Sur rendez-vous, lundi-vendredi',
            'remarques' => 'Consultation medicale generale, orientation vers les specialistes du SSU',
            'latitude' => -22.263457167493094,
            'longitude' => 166.4019359379999,
        ],
        [
            'ref' => 'service_mediation',
            'nom' => 'Service Mediation - UNC',
            'mail' => null,
            'localisation' => '145 Avenue James Cook, 98800 Noumea',
            'province' => Province::Sud,
            'site_web' => 'unc.nc/universite/clinique-du-droit-et-de-la-mediation',
            'telephones' => [
                ['numero' => '26 58 00', 'type' => TelephoneType::Fixe],
            ],
            'sous_themes' => ['harcelement'],
            'horaires' => 'Sur rendez-vous, lundi-vendredi',
            'remarques' => "Mediation et resolution des conflits au sein de l'universite",
            'latitude' => -22.2735,
            'longitude' => 166.4590,
        ],
    ];

    public function run(): void
    {
        $sousThemeIds = SousTheme::pluck('id', 'ref');
        $ordreParSousTheme = [];

        foreach (self::CONTACTS as $donnees) {
            $contact = Contact::updateOrCreate(
                ['ref' => $donnees['ref']],
                [
                    'nom' => $donnees['nom'],
                    'prenom' => null,
                    'mail' => $donnees['mail'],
                    'localisation' => $donnees['localisation'],
                    'province' => $donnees['province'],
                    'site_web' => $donnees['site_web'],
                    'horaires' => $donnees['horaires'] ?? null,
                    'remarques' => $donnees['remarques'] ?? null,
                    'gratuit' => $donnees['gratuit'] ?? null,
                    'anonyme' => null,
                    'actif' => $donnees['actif'] ?? true,
                    'latitude' => $donnees['latitude'] ?? null,
                    'longitude' => $donnees['longitude'] ?? null,
                    'position_territoire' => $donnees['position_territoire'] ?? false,
                ]
            );

            $contact->telephones()->delete();
            foreach ($donnees['telephones'] as $telephone) {
                Telephone::create([
                    'contact_id' => $contact->id,
                    'numero' => $telephone['numero'],
                    'type' => $telephone['type'],
                    'numero_vert' => $telephone['numero_vert'] ?? false,
                    'libelle' => null,
                ]);
            }

            $pivot = [];
            foreach ($donnees['sous_themes'] as $ref) {
                $sousThemeId = $sousThemeIds[$ref]
                    ?? throw new RuntimeException("Sous-theme inconnu en base : {$ref} (contact {$donnees['ref']})");

                $ordreParSousTheme[$sousThemeId] ??= 0;
                $pivot[$sousThemeId] = ['ordre' => $ordreParSousTheme[$sousThemeId]++];
            }

            $contact->sousThemes()->sync($pivot);
        }
    }
}
