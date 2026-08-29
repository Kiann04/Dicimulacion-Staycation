import React from "react";
import Link from "next/link";
import { Compass, Phone, Mail, MapPin, Shield, Instagram, Facebook } from "lucide-react";

export function Footer() {
  return (
    <footer className="border-t border-border bg-card text-card-foreground">
      <div className="container mx-auto py-14 px-6">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
          {/* Column 1: Brand */}
          <div className="space-y-4">
            <div className="flex items-center gap-2.5">
              <div className="h-9 w-9 rounded-xl bg-primary flex items-center justify-center text-primary-foreground shadow-sm">
                <Compass className="h-4 w-4 text-gold-300" />
              </div>
              <span className="font-serif text-xl font-bold tracking-tight text-foreground">
                Dicimulacion
              </span>
            </div>
            <p className="text-sm text-muted-foreground leading-relaxed">
              Curated private staycation villas and suites in Tagaytay and Cavite. Experience serene weekend getaways, heated private pools, karaoke lounges, and heartfelt Filipino hospitality.
            </p>
            <div className="flex items-center gap-3 pt-2">
              <a
                href="https://facebook.com"
                target="_blank"
                rel="noreferrer"
                className="h-9 w-9 rounded-full bg-muted/70 hover:bg-primary hover:text-white flex items-center justify-center text-muted-foreground transition-colors"
                aria-label="Facebook"
              >
                <Facebook className="h-4 w-4" />
              </a>
              <a
                href="https://instagram.com"
                target="_blank"
                rel="noreferrer"
                className="h-9 w-9 rounded-full bg-muted/70 hover:bg-primary hover:text-white flex items-center justify-center text-muted-foreground transition-colors"
                aria-label="Instagram"
              >
                <Instagram className="h-4 w-4" />
              </a>
            </div>
          </div>

          {/* Column 2: Quick Links */}
          <div className="space-y-4">
            <h4 className="font-serif text-sm font-semibold uppercase tracking-wider text-foreground">
              Staycation Retreats
            </h4>
            <ul className="space-y-2.5 text-sm text-muted-foreground">
              <li>
                <Link href="/#villas" className="hover:text-primary transition-colors">
                  Villa Sol y Luna (Tagaytay)
                </Link>
              </li>
              <li>
                <Link href="/#villas" className="hover:text-primary transition-colors">
                  Casa Moderna Forest View (Silang)
                </Link>
              </li>
              <li>
                <Link href="/#villas" className="hover:text-primary transition-colors">
                  The Glass House Stay (Alfonso)
                </Link>
              </li>
              <li>
                <Link href="/#villas" className="hover:text-primary transition-colors">
                  The Rustic Loft Retreat (Amadeo)
                </Link>
              </li>
            </ul>
          </div>

          {/* Column 3: Guest Support & Legal */}
          <div className="space-y-4">
            <h4 className="font-serif text-sm font-semibold uppercase tracking-wider text-foreground">
              Guest Services
            </h4>
            <ul className="space-y-2.5 text-sm text-muted-foreground">
              <li>
                <Link href="/bookings" className="hover:text-primary transition-colors">
                  Booking History & Payments
                </Link>
              </li>
              <li>
                <Link href="/terms" className="hover:text-primary transition-colors">
                  Terms & Booking Conditions
                </Link>
              </li>
              <li>
                <Link href="/privacy" className="hover:text-primary transition-colors">
                  Data Privacy Policy
                </Link>
              </li>
              <li>
                <Link href="/admin/login" className="hover:text-primary transition-colors flex items-center gap-1.5 text-xs text-muted-foreground/70">
                  <Shield className="h-3 w-3" />
                  Staff & Admin Portal
                </Link>
              </li>
            </ul>
          </div>

          {/* Column 4: Contact info */}
          <div className="space-y-4">
            <h4 className="font-serif text-sm font-semibold uppercase tracking-wider text-foreground">
              Direct Inquiries
            </h4>
            <ul className="space-y-3 text-sm text-muted-foreground">
              <li className="flex items-start gap-2.5">
                <MapPin className="h-4 w-4 text-accent shrink-0 mt-0.5" />
                <span>Tagaytay-Nasugbu Highway, Cavite, Philippines</span>
              </li>
              <li className="flex items-center gap-2.5">
                <Phone className="h-4 w-4 text-accent shrink-0" />
                <span>+63 917 123 4567 / (046) 413-0000</span>
              </li>
              <li className="flex items-center gap-2.5">
                <Mail className="h-4 w-4 text-accent shrink-0" />
                <span>reservations@dicimulacionstaycation.com</span>
              </li>
            </ul>
          </div>
        </div>

        <div className="mt-12 pt-6 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-muted-foreground">
          <p>© {new Date().getFullYear()} Dicimulacion Staycation. All rights reserved.</p>
          <div className="flex items-center gap-6">
            <Link href="/terms" className="hover:text-primary transition-colors">
              Terms & Conditions
            </Link>
            <Link href="/privacy" className="hover:text-primary transition-colors">
              Privacy Policy
            </Link>
          </div>
        </div>
      </div>
    </footer>
  );
}
