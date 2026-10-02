# Diagnostic — Orange Money : SMS #150*50# au lieu du prompt sur le téléphone

> Constaté en production les 01 et 02/10/2026 sur 2 clients différents
> (SMS « composez #150*50# », puis « Le transfert de 52 FCFA vers 659924557
> … n'a pas été accepté par le destinataire », et « USSDC: ESME fault » au
> #150*50#). MTN fonctionne de bout en bout (prompt USSD + reversement).

## 1. Ce que dit la doc AangaraaPay (https://aangaraa-pay.com/integrate-aangaraa-pay)

- Endpoint utilisé par EduPay : `POST /api/v1/no_redirect/payment`.
  Champs : `phone_number`, `amount` (string), `description`, `app_key`,
  `transaction_id`, `notify_url`, `operator`, `devise_id`, + `return_url`
  dans l'exemple.
- Promesse de la doc, section « Réponses par opérateur » :
  - **MTN** : « Le client reçoit un **prompt USSD** sur son téléphone »
    (payToken UUID).
  - **Orange** : « Le client reçoit une **notification Orange Money** pour
    approuver le paiement » (payToken `MP…`) — la doc ne promet JAMAIS un
    push USSD pour Orange, seulement une notification.
- Le flux de collecte Orange observé (SMS → menu #150*50# → transfert vers
  le portefeuille marchand 659924557 « PIALOA TECH ») est le circuit
  marchand Orange Money côté **opérateur/prestataire** : il est choisi par
  la configuration du compte AangaraaPay chez Orange, pas par le payload.

## 2. Ce que notre code envoie (vérifié champ par champ)

`AangaraaPayService::initierPaiement()` poste sur `/no_redirect/payment` :

| Champ doc          | EduPay                                   | Conforme |
|--------------------|------------------------------------------|----------|
| phone_number       | `237` + 9 chiffres (normalisé)           | ✅       |
| amount (string)    | `(string) $montant_total_paye`           | ✅       |
| description        | `EduPay — <catégorie> — <apprenant>`     | ✅       |
| app_key            | `AANGARAA_APP_KEY`                       | ✅       |
| transaction_id     | référence `EP2026-XXXXX`                 | ✅       |
| notify_url         | `…/webhook/aangaraapay`                  | ✅       |
| operator           | `Orange_Cameroon` (mode choisi)          | ✅       |
| devise_id          | `XAF`                                    | ✅       |
| return_url         | envoyé pour Orange depuis commit b5f3c39 | ✅       |

Le payload est **identique à l'exemple de la doc**. Les initiations Orange
réelles (EP2026-6LABA, EP2026-QL7RA) ont reçu `201` + `PENDING` + payToken
`MP…` : AangaraaPay a **accepté** chaque transaction ; l'échec arrive
ensuite, dans le circuit Orange.

Historique utile (commits déjà poussés) :
- `2fe9c8c` (01/10 20:55) : le mode réel est envoyé (avant, tout partait
  `mtn_momo` — cause des essais Orange du 01/10 matin).
- `b5f3c39` (02/10 10:37) : `return_url` ajouté pour Orange seul → payload
  désormais identique à l'exemple complet de la doc. **Les deux captures
  client sont ANTÉRIEURES à ce déploiement** : aucun essai réel n'a encore
  couru avec le payload complet.

## 3. Conclusion

Rien dans notre code ne choisit « SMS au lieu de push » : le canal Orange
(notification avec SMS #150*50# + transfert vers 659924557) est la manière
dont le compte AangaraaPay/PIALOA TECH est provisionné chez Orange Money.
L'« USSDC: ESME fault » est une erreur de la passerelle USSD d'Orange
elle-même. Ces deux éléments sont hors de portée de l'API appelée.

## 4. Question à poser au support AangaraaPay (brouillon prêt)

> Bonjour,
>
> Sur notre service EduPay (app_key en production), les paiements
> `operator: "Orange_Cameroon"` via `POST /api/v1/no_redirect/payment`
> n'ouvrent pas de demande de confirmation sur le téléphone du client :
> il reçoit un SMS lui demandant de composer `#150*50#`, et le transfert
> vers 659924557 (PIALOA TECH) est ensuite refusé (« n'a pas été accepté
> par le destinataire »). Composer `#150*50#` manuellement renvoie
> « USSDC: ESME fault ». Nos transactions : EP2026-6LABA (01/10),
> EP2026-QL7RA (02/10, payToken MP261002…, ID opérateur
> MP261002.0821.B13137). Nos payloads reproduisent exactement l'exemple de
> votre documentation (y compris `return_url` depuis aujourd'hui).
> Nos paiements MTN_Cameroon, eux, reçoivent bien un prompt USSD.
>
> Questions :
> 1. Le compte PIALOA TECH est-il provisionné chez Orange Money pour le
>    **push de demande de paiement (MP/pay push)** ou uniquement pour la
>    collecte par menu `#150*50#` ?
> 2. Si le push existe, quel paramètre ou quelle activation faut-il pour
>    que la notification Orange s'affiche directement sur le téléphone ?
> 3. Le refus « transfert non accepté par le destinataire » vers 659924557
>    et l'erreur « USSDC: ESME fault » viennent-elles de votre côté ?
>
> Merci — chaque essai Orange nous fait perdre des clients.

Contacts : contact@aangaraa-pay.com — +237 674 506 841 (Yaoundé, Melen).

## 5. Si le support confirme que le push Orange n'est pas disponible

Options de repli (aucune ne touche MTN) :
1. Assumer le flux SMS : la page d'attente Orange affiche déjà les
   instructions `#150*50#` (commit 6019640) — l'expliquer au client.
2. Demander si le flux `/redirect/payment` (page de paiement hébergée
   AangaraaPay, même doc) déclenche un meilleur parcours Orange.
3. En dernier recours, envisager un second agrégateur pour Orange.

-- Buffy, 02/10/2026
