import React from "react";
import Link from "next/link";
import { Staycation } from "@/types";
import { formatPHP } from "@/lib/utils";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { MapPin, Users, Star, ArrowRight } from "lucide-react";

interface PropertyCardProps {
  property: Staycation;
}

export function PropertyCard({ property }: PropertyCardProps) {
  return (
    <div className="group rounded-2xl border border-border/80 bg-card overflow-hidden shadow-subtle hover:shadow-elevated transition-all duration-300 flex flex-col justify-between">
      {/* Image Container */}
      <div className="relative h-64 w-full overflow-hidden bg-muted">
        <img
          src={property.image_url || property.house_image}
          alt={property.house_name}
          className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
        />
        <div className="absolute top-3.5 left-3.5">
          <Badge variant={property.house_availability === "available" ? "emerald" : "rose"}>
            {property.house_availability === "available" ? "Available" : "Unavailable"}
          </Badge>
        </div>
        <div className="absolute bottom-3.5 right-3.5 rounded-xl bg-black/75 backdrop-blur-md px-3 py-1.5 text-white text-xs font-semibold">
          {formatPHP(property.house_price)} <span className="font-normal text-white/80">/ night</span>
        </div>
      </div>

      {/* Body Content */}
      <div className="p-6 flex-1 flex flex-col justify-between">
        <div>
          <div className="flex items-center gap-1.5 text-xs text-muted-foreground mb-2">
            <MapPin className="h-3.5 w-3.5 text-accent shrink-0" />
            <span className="truncate">{property.house_location}</span>
          </div>

          <h3 className="font-serif text-xl font-bold text-foreground group-hover:text-primary transition-colors line-clamp-1">
            {property.house_name}
          </h3>

          <p className="mt-2.5 text-xs text-muted-foreground line-clamp-2 leading-relaxed">
            {property.house_description}
          </p>

          <div className="mt-4 flex items-center justify-between text-xs py-3 border-y border-border/60">
            <div className="flex items-center gap-1.5 font-medium text-foreground">
              <Users className="h-4 w-4 text-primary" />
              <span>Up to 12 Guests</span>
            </div>
            <div className="flex items-center gap-1 font-semibold text-amber-600">
              <Star className="h-4 w-4 fill-amber-500 text-amber-500" />
              <span>{property.average_rating || "4.9"}</span>
              <span className="text-muted-foreground font-normal">({property.total_reviews || 30})</span>
            </div>
          </div>
        </div>

        <div className="mt-5">
          <Link href={`/staycation/${property.id}`}>
            <Button variant="default" className="w-full font-semibold gap-2 rounded-xl">
              View Villa & Book
              <ArrowRight className="h-4 w-4" />
            </Button>
          </Link>
        </div>
      </div>
    </div>
  );
}
