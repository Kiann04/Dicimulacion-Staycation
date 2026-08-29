import { Staycation } from "@/types";

/**
 * Isolated placeholder data used ONLY when backend API data is unavailable.
 */
export const STAYCATION_PLACEHOLDERS: Staycation[] = [
  {
    id: 1,
    house_name: "Villa Sol y Luna (Private Pool & Balcony)",
    house_description: "A private 3-bedroom sanctuary featuring an exclusive heated pool, smart karaoke lounge, outdoor grill, high-speed 200Mbps Wi-Fi, and scenic mountain breeze in Tagaytay. Perfect for up to 12 family members or friends looking to unwind in serenity.",
    house_price: 6500,
    house_location: "Tagaytay City, Cavite (Near Sky Ranch)",
    house_availability: "available",
    house_image: "https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=1200&auto=format&fit=crop&q=80",
    image_url: "https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=1200&auto=format&fit=crop&q=80",
    average_rating: 4.9,
    total_reviews: 48,
    star_counts: { "5": 42, "4": 5, "3": 1, "2": 0, "1": 0 },
    images: [
      { id: 1, image_path: "", image_url: "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&auto=format&fit=crop&q=80" },
      { id: 2, image_path: "", image_url: "https://images.unsplash.com/photo-1613490493576-7fde63acd811?w=800&auto=format&fit=crop&q=80" },
    ],
  },
  {
    id: 2,
    house_name: "Casa Moderna Forest View Villa",
    house_description: "Immerse in contemporary luxury with floor-to-ceiling glass windows, an infinity plunge pool facing the lush pine forest, billiard table, chef's kitchen, and high-end aesthetic interiors designed for memorable staycation moments.",
    house_price: 7800,
    house_location: "Silang, Cavite (5 mins to Tagaytay)",
    house_availability: "available",
    house_image: "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&auto=format&fit=crop&q=80",
    image_url: "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&auto=format&fit=crop&q=80",
    average_rating: 4.8,
    total_reviews: 36,
    star_counts: { "5": 30, "4": 5, "3": 1, "2": 0, "1": 0 },
  },
  {
    id: 3,
    house_name: "The Glass House Stay & Lounge",
    house_description: "Boasting minimalist architecture, ambient garden lighting, private jacuzzi tub, outdoor cinema setup, and complete kitchen amenities. The ultimate private haven for couples, celebrations, and intimate family reunions.",
    house_price: 5200,
    house_location: "Alfonso, Cavite",
    house_availability: "available",
    house_image: "https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=1200&auto=format&fit=crop&q=80",
    image_url: "https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=1200&auto=format&fit=crop&q=80",
    average_rating: 4.95,
    total_reviews: 29,
  },
];
