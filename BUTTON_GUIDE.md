# Button Styling Guide

This guide covers the modern button styling system with role-based variants and responsive design.

## Button Variants

### Primary Button
Used for main actions and primary navigation.
```html
<a href="#" class="btn-primary">Primary Action</a>
<button class="btn-primary">Primary Button</button>
```

### Success Button
Used for positive actions like approve, save, create.
```html
<a href="#" class="btn-success">Approve</a>
<button class="btn-success">Save Changes</button>
```

### Danger Button
Used for destructive actions like delete, reject, cancel.
```html
<a href="#" class="btn-danger">Delete</a>
<button class="btn-danger">Reject</button>
```

### Warning Button
Used for cautionary actions that need user attention.
```html
<a href="#" class="btn-warning">Warning Action</a>
<button class="btn-warning">Proceed with Caution</button>
```

### Info Button
Used for informational or secondary navigation.
```html
<a href="#" class="btn-info">Learn More</a>
<button class="btn-info">More Information</button>
```

### Secondary Button
Used for less important actions.
```html
<a href="#" class="btn-secondary">Cancel</a>
<button class="btn-secondary">Back</button>
```

## Button Outline Variants

For less prominent actions, use outline buttons:
```html
<a href="#" class="btn-outline-primary">Outline Primary</a>
<button class="btn-outline-secondary">Outline Secondary</button>
```

## Button Sizes

### Small Button
```html
<a href="#" class="btn-primary btn-sm">Small</a>
```

### Large Button
```html
<a href="#" class="btn-primary btn-lg">Large</a>
```

## Button Block (Full Width)
```html
<button class="btn-primary btn-block">Full Width Button</button>
```

## Button Groups
Group related buttons together:
```html
<div class="btn-group">
    <a href="#" class="btn-primary">Action 1</a>
    <a href="#" class="btn-success">Action 2</a>
    <a href="#" class="btn-danger">Action 3</a>
</div>
```

## Link Button
For inline text links styled as buttons:
```html
<a href="#" class="btn-link">Click here</a>
```

## Usage Examples in PHP

### Admin Dashboard
```php
<div class="btn-group" style="margin-bottom: 30px;">
    <a href="members.php" class="btn-primary">Manage Members</a>
    <a href="loans.php" class="btn-info">Manage Loans</a>
    <a href="repayments.php" class="btn-success">View Repayments</a>
</div>
```

### Table Actions
```php
<td>
    <div class="btn-group">
        <a href="edit.php?id=<?php echo $id; ?>" class="btn-primary btn-sm">Edit</a>
        <a href="delete.php?id=<?php echo $id; ?>" class="btn-danger btn-sm">Delete</a>
    </div>
</td>
```

### Forms
```php
<div class="btn-group">
    <button type="submit" class="btn-success">Save</button>
    <button type="reset" class="btn-secondary">Clear</button>
    <a href="back.php" class="btn-link">Cancel</a>
</div>
```

## Color Reference

- **Primary**: Green (#2d6a4f)
- **Success**: Green (#28a745)
- **Danger**: Red (#e63946)
- **Warning**: Amber (#ffc107)
- **Info**: Teal (#17a2b8)
- **Secondary**: Gray (#6c757d)

## Mobile Responsiveness

All buttons are fully responsive. The application includes:
- Hamburger menu (☰) on mobile devices (< 768px)
- Collapsible sidebar navigation
- Responsive button groups with wrapping
- Touch-friendly button sizes

## Responsive Breakpoints

### Tablet & Below (≤ 768px)
- Hamburger menu appears
- Sidebar becomes full-width and slides in
- Button groups wrap as needed

### Mobile (≤ 480px)
- Smaller font sizes
- Reduced padding on buttons
- Optimized tap targets
- Single-column layouts

## Interactive Features

- **Hover Effects**: Buttons lift slightly and show shadow
- **Active State**: Visual feedback on click
- **Transitions**: Smooth 0.3s animations
- **Accessibility**: Proper padding and text contrast
