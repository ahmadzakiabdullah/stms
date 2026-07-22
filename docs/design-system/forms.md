# Forms

## Stack

- **React Hook Form** — performant form state management with minimal re-renders
- **Zod** — schema-based validation with type inference
- **shadcn/ui form components** — pre-built, accessible form fields (Input, Select, Checkbox, etc.)

## Pattern

Forms follow a controller-based pattern using `react-hook-form`'s `useForm` hook combined with Zod schemas:

```tsx
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

const formSchema = z.object({
  name: z.string().min(1, 'Name is required'),
  description: z.string().optional(),
});

type FormData = z.infer<typeof formSchema>;

const form = useForm<FormData>({
  resolver: zodResolver(formSchema),
});
```

## Store / Update Separation

Each feature has distinct form request types and schemas:

- **StoreRequest** / **CreateSchema** — validation for creation (all required fields, no ID)
- **UpdateRequest** / **UpdateSchema** — validation for updates (optional fields, existing ID context)

This separation keeps validation rules explicit and avoids conditional logic inside a single schema.

## Server Communication

Forms use Inertia's `router.post`, `router.put`, or `router.patch` for submission. Server-side validation errors are returned via Inertia's error bag and displayed inline using shadcn/ui's `FormMessage` component:

```tsx
<FormField
  control={form.control}
  name="name"
  render={({ field }) => (
    <FormItem>
      <FormLabel>Name</FormLabel>
      <FormControl>
        <Input {...field} />
      </FormControl>
      <FormMessage />
    </FormItem>
  )}
/>
```

## Field Components

All standard form controls are available as shadcn/ui components: `Input`, `Textarea`, `Select`, `Checkbox`, `RadioGroup`, `Switch`, and `DatePicker`. Each wraps a Radix UI primitive with consistent styling and accessibility.
