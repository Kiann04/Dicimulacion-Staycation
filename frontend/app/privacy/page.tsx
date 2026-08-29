import React from "react";

export default function PrivacyPage() {
  return (
    <div className="min-h-screen py-16 bg-background">
      <div className="container mx-auto px-6 max-w-3xl">
        <h1 className="font-serif text-3xl sm:text-4xl font-bold text-foreground mb-4">
          Data Privacy Policy
        </h1>
        <p className="text-xs text-muted-foreground mb-8">Effective Date: January 1, 2026</p>

        <div className="space-y-6 text-sm text-foreground/90 leading-relaxed">
          <section className="space-y-2">
            <h2 className="font-serif text-lg font-bold text-foreground">1. Data Collection</h2>
            <p>
              In compliance with the Data Privacy Act of 2012 (RA 10173), Dicimulacion Staycation collects personal information such as guest full name, contact numbers, email address, and transaction screenshots solely for the purpose of booking confirmations, security gate passes, and guest service communication.
            </p>
          </section>

          <section className="space-y-2">
            <h2 className="font-serif text-lg font-bold text-foreground">2. Security & Storage</h2>
            <p>
              All personal records and payment files are stored securely with encrypted access restricted solely to authorized concierge staff and system administrators. We do not sell or transfer your data to third parties.
            </p>
          </section>
        </div>
      </div>
    </div>
  );
}
