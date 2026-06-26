<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    /**
     * Test: Afficher le formulaire de contact
     */
    public function test_can_view_contact_form(): void
    {
        $response = $this->get('/contact');

        $response->assertStatus(200);
        $response->assertViewIs('public.contact');
    }

    /**
     * Test: Soumettre un message de contact valide
     */
    public function test_can_submit_contact_message(): void
    {
        Mail::fake();

        $data = [
            'name' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'phone' => '+237 654 862 989',
            'subject' => 'Question sur les frais de scolarité',
            'message' => 'Bonjour, j\'aimerais savoir comment fonctionne le système de paiement...',
        ];

        echo "\n========== TEST: SUBMIT CONTACT MESSAGE ==========\n";
        echo "✅ Envoi du message...\n";
        echo "   📧 De: {$data['email']}\n";
        echo "   👤 Nom: {$data['name']}\n";
        echo "   📱 Tél: {$data['phone']}\n";
        echo "   📝 Sujet: {$data['subject']}\n\n";

        $response = $this->post('/contact', $data);

        echo "✅ Message soumis avec succès\n";
        echo "✅ Redirection vers: /contact\n\n";

        $response->assertRedirect(route('contact'));
        $response->assertSessionHas('success');

        // Vérifier que l'email a été "envoyé" (en test, il est fake)
        Mail::assertSent(\App\Mail\ContactMessageMail::class);

        echo "========== FIN DU TEST ==========\n\n";
    }

    /**
     * Test: Message sans nom
     */
    public function test_contact_message_requires_name(): void
    {
        Mail::fake();

        $response = $this->post('/contact', [
            'email' => 'test@example.com',
            'phone' => '+237 654 862 989',
            'subject' => 'Test',
            'message' => 'Test message',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('name');
    }

    /**
     * Test: Message sans email
     */
    public function test_contact_message_requires_email(): void
    {
        Mail::fake();

        $response = $this->post('/contact', [
            'name' => 'Jean',
            'phone' => '+237 654 862 989',
            'subject' => 'Test',
            'message' => 'Test message',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
    }

    /**
     * Test: Message sans téléphone
     */
    public function test_contact_message_requires_phone(): void
    {
        Mail::fake();

        $response = $this->post('/contact', [
            'name' => 'Jean',
            'email' => 'test@example.com',
            'subject' => 'Test',
            'message' => 'Test message',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('phone');
    }

    /**
     * Test: Message sans sujet
     */
    public function test_contact_message_requires_subject(): void
    {
        Mail::fake();

        $response = $this->post('/contact', [
            'name' => 'Jean',
            'email' => 'test@example.com',
            'phone' => '+237 654 862 989',
            'message' => 'Test message',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('subject');
    }

    /**
     * Test: Message sans texte
     */
    public function test_contact_message_requires_message_text(): void
    {
        Mail::fake();

        $response = $this->post('/contact', [
            'name' => 'Jean',
            'email' => 'test@example.com',
            'phone' => '+237 654 862 989',
            'subject' => 'Test',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('message');
    }

    /**
     * Test: Email invalide
     */
    public function test_contact_message_requires_valid_email(): void
    {
        Mail::fake();

        $response = $this->post('/contact', [
            'name' => 'Jean',
            'email' => 'invalid-email',
            'phone' => '+237 654 862 989',
            'subject' => 'Test',
            'message' => 'Test message',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
    }

    /**
     * Test: Limite de caractères pour le nom (max: 100)
     */
    public function test_contact_message_name_max_length(): void
    {
        Mail::fake();

        $longName = str_repeat('a', 101); // Plus que 100

        $response = $this->post('/contact', [
            'name' => $longName,
            'email' => 'test@example.com',
            'phone' => '+237 654 862 989',
            'subject' => 'Test',
            'message' => 'Test message',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('name');
    }

    /**
     * Test: Limite de caractères pour le sujet (max: 100)
     */
    public function test_contact_message_subject_max_length(): void
    {
        Mail::fake();

        $longSubject = str_repeat('a', 101); // Plus que 100

        $response = $this->post('/contact', [
            'name' => 'Jean',
            'email' => 'test@example.com',
            'phone' => '+237 654 862 989',
            'subject' => $longSubject,
            'message' => 'Test message',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('subject');
    }

    /**
     * Test: Limite de caractères pour le message (max: 2000)
     */
    public function test_contact_message_message_max_length(): void
    {
        Mail::fake();

        $longMessage = str_repeat('a', 2001); // Plus que 2000

        $response = $this->post('/contact', [
            'name' => 'Jean',
            'email' => 'test@example.com',
            'phone' => '+237 654 862 989',
            'subject' => 'Test',
            'message' => $longMessage,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('message');
    }

    /**
     * 🌟 TEST PRINCIPAL: Flux complet avec LOGS visibles
     */
    public function test_complete_contact_flow_with_logs(): void
    {
        Mail::fake();

        echo "\n╔════════════════════════════════════════════════════╗\n";
        echo "║  🌟 TEST: FLUX COMPLET DE CONTACT AVEC LOGS       ║\n";
        echo "╚════════════════════════════════════════════════════╝\n\n";

        // 1️⃣ ÉTAPE 1: Consulter le formulaire
        echo "1️⃣  ÉTAPE 1: Consultation du formulaire\n";
        $response = $this->get('/contact');
        $response->assertStatus(200);
        echo "   ✅ Formulaire affiché (status 200)\n\n";

        // 2️⃣ ÉTAPE 2: Préparer les données
        echo "2️⃣  ÉTAPE 2: Préparation du message\n";
        $contactData = [
            'name' => 'Marie Talla',
            'email' => 'marie.talla@gmail.com',
            'phone' => '+237 696 123 456',
            'subject' => 'Problème de paiement de frais scolaires',
            'message' => 'Bonjour, je n\'arrive pas à payer les frais de scolarité de mon fils. Pourriez-vous m\'aider?',
        ];

        echo "   📧 Email: {$contactData['email']}\n";
        echo "   👤 Nom: {$contactData['name']}\n";
        echo "   📱 Téléphone: {$contactData['phone']}\n";
        echo "   📋 Sujet: {$contactData['subject']}\n";
        echo "   📝 Message: " . substr($contactData['message'], 0, 50) . "...\n\n";

        // 3️⃣ ÉTAPE 3: Soumettre le formulaire
        echo "3️⃣  ÉTAPE 3: Soumission du formulaire\n";
        $response = $this->post('/contact', $contactData);
        echo "   ✅ Formulaire soumis avec succès\n";
        echo "   ✅ Statut de réponse: " . $response->status() . "\n";
        echo "   ✅ Redirection: /contact\n\n";

        // 4️⃣ ÉTAPE 4: Vérifications
        echo "4️⃣  ÉTAPE 4: Vérifications\n";
        $response->assertRedirect(route('contact'));
        $response->assertSessionHas('success');
        Mail::assertSent(\App\Mail\ContactMessageMail::class);

        echo "   ✅ Message redirigé vers la page de contact\n";
        echo "   ✅ Message de succès dans la session\n";
        echo "   ✅ Email envoyé (ou simulé en test)\n\n";

        echo "╔════════════════════════════════════════════════════╗\n";
        echo "║  ✨ TEST RÉUSSI! Vérifiez les LOGS ci-dessous  ║\n";
        echo "╚════════════════════════════════════════════════════╝\n\n";
    }

    /**
     * Test: Plusieurs messages d'affilée (pour voir dans les logs)
     */
    public function test_multiple_contact_messages(): void
    {
        Mail::fake();

        echo "\n📊 TEST: Plusieurs messages de contact\n\n";

        $messages = [
            [
                'name' => 'Pierre Martin',
                'email' => 'pierre@example.com',
                'phone' => '+237 600 100 001',
                'subject' => 'Question 1',
                'message' => 'Premier message',
            ],
            [
                'name' => 'Alice Dupont',
                'email' => 'alice@example.com',
                'phone' => '+237 600 100 002',
                'subject' => 'Question 2',
                'message' => 'Deuxième message',
            ],
            [
                'name' => 'Bob Lemoine',
                'email' => 'bob@example.com',
                'phone' => '+237 600 100 003',
                'subject' => 'Question 3',
                'message' => 'Troisième message',
            ],
        ];

        foreach ($messages as $index => $data) {
            echo "Message " . ($index + 1) . "/3 - De: {$data['email']}\n";
            
            $response = $this->post('/contact', $data);
            $response->assertSessionHas('success');
        }

        echo "\n✅ 3 messages envoyés! Regardez les logs pour voir tous les détails.\n\n";
    }
}
