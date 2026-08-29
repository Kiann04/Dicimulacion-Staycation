import React from "react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Compass, Home } from "lucide-react";

export default function NotFound() {
  return (
    <div className="min-h-[75vh] flex flex-col items-center justify-center text-center p-6 bg-background">
      <div className="h-14 w-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mb-4">
        <Compass className="h-8 w-8 text-gold-500" />
      </div>
      <h1 className="font-serif text-4xl sm:text-5xl font-bold text-foreground">404</h1>
      <h2 className="font-serif text-xl sm:text-2xl font-semibold text-foreground mt-2">
        Page Not Found
      </h2>
      <p className="text-sm text-muted-foreground mt-2 max-w-md">
        The staycation villa or page you are looking for might have been moved or is currently unavailable.
      </p>
      <div className="mt-6">
        <Link href="/">
          <Button variant="gold" className="gap-2">
            <Home className="h-4 w-4" />
            Back to Home
          </Button>
        </Link>
      </div>
    </div>
  );
}
