"use client";

import React, { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { staycationService } from "@/lib/services/staycationService";
import { Staycation } from "@/lib/types";
import { formatPHP } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Dialog } from "@/components/ui/dialog";
import { Plus, Edit, Power, MapPin, Users, Star, Home } from "lucide-react";

export default function AdminStaycationsPage() {
  const queryClient = useQueryClient();
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [editingVilla, setEditingVilla] = useState<Staycation | null>(null);

  // Form State
  const [houseName, setHouseName] = useState("");
  const [houseDesc, setHouseDesc] = useState("");
  const [housePrice, setHousePrice] = useState(6500);
  const [houseLocation, setHouseLocation] = useState("");
  const [houseAvailability, setHouseAvailability] = useState("available");
  const [mainImageFile, setMainImageFile] = useState<File | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { data: villas = [], isLoading } = useQuery({
    queryKey: ["admin-staycations"],
    queryFn: () => staycationService.getAll(),
  });

  const toggleAvailabilityMutation = useMutation({
    mutationFn: (id: number) => staycationService.toggleAvailability(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-staycations"] });
      alert("Property availability updated.");
    },
  });

  const handleCreateSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    const formData = new FormData();
    formData.append("house_name", houseName);
    formData.append("house_description", houseDesc);
    formData.append("house_price", String(housePrice));
    formData.append("house_location", houseLocation);
    formData.append("house_availability", houseAvailability);
    if (mainImageFile) formData.append("house_image", mainImageFile);

    try {
      await staycationService.create(formData);
      queryClient.invalidateQueries({ queryKey: ["admin-staycations"] });
      setIsAddModalOpen(false);
      // Reset form
      setHouseName("");
      setHouseDesc("");
      setHouseLocation("");
      alert("New staycation villa added successfully!");
    } catch {
      alert("Failed to create staycation villa.");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleEditSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingVilla) return;
    setIsSubmitting(true);

    const formData = new FormData();
    formData.append("house_name", editingVilla.name || editingVilla.house_name || "");
    formData.append("house_description", editingVilla.description || editingVilla.house_description || "");
    formData.append("house_price", String(editingVilla.price_per_night || editingVilla.house_price || 0));
    formData.append("house_location", editingVilla.location || editingVilla.house_location || "");
    formData.append("house_availability", editingVilla.availability || editingVilla.house_availability || "available");

    try {
      await staycationService.update(editingVilla.id, formData);
      queryClient.invalidateQueries({ queryKey: ["admin-staycations"] });
      setEditingVilla(null);
      alert("Staycation details updated successfully!");
    } catch {
      alert("Failed to update staycation.");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="space-y-8 max-w-7xl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span className="text-xs font-bold uppercase tracking-widest text-accent block mb-1">
            Properties Catalog
          </span>
          <h1 className="font-serif text-3xl font-bold text-foreground">Staycations Management</h1>
        </div>

        <Button onClick={() => setIsAddModalOpen(true)} variant="gold" size="sm" className="gap-2">
          <Plus className="h-4 w-4" />
          Add New Staycation
        </Button>
      </div>

      {/* Grid */}
      {isLoading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-pulse">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-80 rounded-2xl bg-muted" />
          ))}
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {villas.map((villa) => (
            <div
              key={villa.id}
              className="rounded-2xl border border-border/80 bg-card overflow-hidden shadow-subtle flex flex-col justify-between"
            >
              <div>
                <div className="relative h-48 w-full bg-muted">
                  <img
                    src={villa.image_url || villa.house_image}
                    alt={villa.house_name}
                    className="h-full w-full object-cover"
                  />
                  <div className="absolute top-3 left-3">
                    <Badge variant={villa.house_availability === "available" ? "emerald" : "rose"}>
                      {villa.house_availability}
                    </Badge>
                  </div>
                </div>

                <div className="p-5">
                  <div className="flex items-center gap-1 text-xs text-muted-foreground mb-1.5">
                    <MapPin className="h-3.5 w-3.5 text-accent" />
                    <span>{villa.house_location}</span>
                  </div>

                  <h3 className="font-serif text-lg font-bold text-foreground line-clamp-1">
                    {villa.house_name}
                  </h3>

                  <p className="mt-2 text-xs text-muted-foreground line-clamp-2">
                    {villa.house_description}
                  </p>

                  <div className="mt-4 pt-3 border-t border-border flex items-center justify-between">
                    <div>
                      <span className="text-xs text-muted-foreground">Price: </span>
                      <span className="font-serif font-bold text-sm text-foreground">
                        {formatPHP(villa.house_price)}/night
                      </span>
                    </div>
                    <div className="text-xs text-amber-600 font-semibold flex items-center gap-1">
                      <Star className="h-3.5 w-3.5 fill-amber-500 text-amber-500" />
                      <span>{villa.average_rating || "4.9"}</span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Actions */}
              <div className="p-5 pt-0 border-t border-border/40 mt-2 flex items-center justify-between gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  className="text-xs flex-1 gap-1.5"
                  onClick={() => setEditingVilla(villa)}
                >
                  <Edit className="h-3.5 w-3.5" />
                  Edit
                </Button>

                <Button
                  variant={villa.house_availability === "available" ? "secondary" : "default"}
                  size="sm"
                  className="text-xs flex-1 gap-1.5"
                  onClick={() => toggleAvailabilityMutation.mutate(villa.id)}
                >
                  <Power className="h-3.5 w-3.5" />
                  {villa.house_availability === "available" ? "Disable" : "Enable"}
                </Button>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* ADD STAYCATION MODAL */}
      <Dialog
        isOpen={isAddModalOpen}
        onClose={() => setIsAddModalOpen(false)}
        title="Add New Staycation Villa"
        description="Add a new luxury villa listing to your booking catalog."
        maxWidth="lg"
      >
        <form onSubmit={handleCreateSubmit} className="space-y-4 text-xs sm:text-sm">
          <div>
            <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
              Villa Name
            </label>
            <Input
              required
              value={houseName}
              onChange={(e) => setHouseName(e.target.value)}
              placeholder="e.g. Villa Cascata Private Pool"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
              Location / Area
            </label>
            <Input
              required
              value={houseLocation}
              onChange={(e) => setHouseLocation(e.target.value)}
              placeholder="e.g. Tagaytay City, Cavite"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
              Nightly Rate (PHP)
            </label>
            <Input
              type="number"
              min={500}
              required
              value={housePrice}
              onChange={(e) => setHousePrice(Number(e.target.value))}
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
              Villa Description & Amenities
            </label>
            <Textarea
              required
              rows={3}
              value={houseDesc}
              onChange={(e) => setHouseDesc(e.target.value)}
              placeholder="Describe bedrooms, heated pool specs, karaoke, and special views..."
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
              Cover Image Upload
            </label>
            <input
              type="file"
              accept="image/*"
              required
              onChange={(e) => {
                if (e.target.files && e.target.files[0]) {
                  setMainImageFile(e.target.files[0]);
                }
              }}
              className="block w-full text-xs text-muted-foreground border border-input rounded-xl p-1.5"
            />
          </div>

          <div className="flex items-center justify-end gap-3 pt-3 border-t border-border">
            <Button type="button" variant="outline" onClick={() => setIsAddModalOpen(false)}>
              Cancel
            </Button>
            <Button type="submit" variant="gold" isLoading={isSubmitting}>
              Create Staycation
            </Button>
          </div>
        </form>
      </Dialog>

      {/* EDIT STAYCATION MODAL */}
      <Dialog
        isOpen={!!editingVilla}
        onClose={() => setEditingVilla(null)}
        title={`Edit - ${editingVilla?.house_name}`}
        maxWidth="lg"
      >
        {editingVilla && (
          <form onSubmit={handleEditSubmit} className="space-y-4 text-xs sm:text-sm">
            <div>
              <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
                Villa Name
              </label>
              <Input
                required
                value={editingVilla.house_name}
                onChange={(e) =>
                  setEditingVilla({ ...editingVilla, house_name: e.target.value })
                }
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
                Location
              </label>
              <Input
                required
                value={editingVilla.house_location}
                onChange={(e) =>
                  setEditingVilla({ ...editingVilla, house_location: e.target.value })
                }
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
                Nightly Rate (PHP)
              </label>
              <Input
                type="number"
                min={500}
                required
                value={editingVilla.house_price}
                onChange={(e) =>
                  setEditingVilla({ ...editingVilla, house_price: Number(e.target.value) })
                }
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
                Description
              </label>
              <Textarea
                required
                rows={4}
                value={editingVilla.house_description}
                onChange={(e) =>
                  setEditingVilla({ ...editingVilla, house_description: e.target.value })
                }
              />
            </div>

            <div className="flex items-center justify-end gap-3 pt-3 border-t border-border">
              <Button type="button" variant="outline" onClick={() => setEditingVilla(null)}>
                Cancel
              </Button>
              <Button type="submit" variant="default" isLoading={isSubmitting}>
                Save Changes
              </Button>
            </div>
          </form>
        )}
      </Dialog>
    </div>
  );
}
